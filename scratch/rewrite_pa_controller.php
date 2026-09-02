<?php

$file = 'C:/Users/itmdu/OneDrive/Documents/Mandau/project/project/kumpulan MP3/mp3/mp3/app/Http/Controllers/Erp/ErpPaymentAdviceController.php';
$content = file_get_contents($file);

// Replace submit, approve, reject, markPaid
$replacement = <<<PHP
    public function submitDetail(Request \$request, ErpPaymentAdviceDetail \$paymentAdviceDetail)
    {
        \$request->validate([
            'invoice_no' => 'required|string|max:100',
        ]);

        if (\$paymentAdviceDetail->approvals()->count() > 0) {
            return redirect()->back()->with('error', 'Payment Advice Detail is already submitted for approval.');
        }

        DB::beginTransaction();
        try {
            // Update the detail with invoice no
            \$paymentAdviceDetail->update([
                'invoice_no' => \$request->invoice_no,
                'approval_status' => 'Submitted'
            ]);

            // Create Approval Flow
            \$amount = \$paymentAdviceDetail->payment_amount_with_tax;
            \$configs = \App\Models\Erp\ErpApprovalConfig::where('record_type', 'payment_advice')
                ->where(function(\$q) use (\$amount) {
                    \$q->whereNull('min_amount')->orWhere('min_amount', '<=', \$amount);
                })
                ->where(function(\$q) use (\$amount) {
                    \$q->whereNull('max_amount')->orWhere('max_amount', '>=', \$amount);
                })
                ->orderBy('level')
                ->get();

            if (\$configs->isEmpty()) {
                // If no dynamic configs, fallback to 1-level CEO/Finance
                \App\Models\Erp\ErpApproval::create([
                    'payment_advice_detail_id' => \$paymentAdviceDetail->id,
                    'level' => 1,
                    'status' => 'Pending',
                ]);
            } else {
                \$isFirst = true;
                foreach (\$configs as \$config) {
                    \App\Models\Erp\ErpApproval::create([
                        'payment_advice_detail_id' => \$paymentAdviceDetail->id,
                        'level' => \$config->level,
                        'assigned_to_role_id' => \$config->role_id,
                        'assigned_to_user_id' => \$config->user_id,
                        'status' => \$isFirst ? 'Pending' : 'Waiting',
                    ]);
                    \$isFirst = false;
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Rincian termin berhasil disubmit untuk approval.');
        } catch (\Exception \$e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal submit approval: ' . \$e->getMessage());
        }
    }

    public function approveDetail(Request \$request, ErpPaymentAdviceDetail \$paymentAdviceDetail)
    {
        \$user = auth()->user();

        DB::beginTransaction();
        try {
            \$activeApproval = \$paymentAdviceDetail->approvals()->where('status', 'Pending')->first();

            if (\$activeApproval) {
                \$isAuthorized = false;
                if (\$user->hasRole('superadmin')) {
                    \$isAuthorized = true;
                } elseif (\$activeApproval->assigned_to_user_id) {
                    if (\$user->id == \$activeApproval->assigned_to_user_id) {
                        \$isAuthorized = true;
                    }
                } elseif (\$activeApproval->assigned_to_role_id) {
                    \$hasRole = \Illuminate\Support\Facades\DB::connection('master')
                        ->table('role_user')
                        ->where('user_id', \$user->id)
                        ->where('role_id', \$activeApproval->assigned_to_role_id)
                        ->exists();
                    if (\$hasRole) {
                        \$isAuthorized = true;
                    }
                }

                if (!\$isAuthorized) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menyetujui termin ini.');
                }

                \$activeApproval->update([
                    'status' => 'Approved',
                    'comments' => \$request->input('comments', 'Approved by ' . \$user->name),
                    'actual_approver_id' => \$user->id,
                    'approved_at' => now(),
                ]);

                // Promote next step if any
                \$nextApproval = \$paymentAdviceDetail->approvals()->where('status', 'Waiting')->orderBy('level')->first();
                if (\$nextApproval) {
                    \$nextApproval->update(['status' => 'Pending']);
                } else {
                    // Fully approved
                    \$paymentAdviceDetail->update([
                        'approval_status' => 'Approved',
                        'approved_date' => now()
                    ]);
                    
                    // Recalculate PA Header
                    if (\$paymentAdviceDetail->paymentAdvice) {
                        \$this->recalculateTotals(\$paymentAdviceDetail->paymentAdvice);
                    }
                }

                DB::commit();
                return redirect()->back()->with('success', 'Approval Rincian Termin berhasil disetujui.');
            }

            // FALLBACK RULES (No active dynamic approval, use legacy flow)
            if (!\$user->hasRole('superadmin') && !\$user->hasRole('finance') && !\$user->hasRole('ceo')) {
                return redirect()->back()->with('error', 'Hanya Finance / CEO / Superadmin yang berhak menyetujui Termin ini.');
            }

            \$paymentAdviceDetail->update([
                'approval_status' => 'Approved',
                'approved_date' => now()
            ]);

            \App\Models\Erp\ErpApproval::create([
                'payment_advice_detail_id' => \$paymentAdviceDetail->id,
                'level' => 1,
                'status' => 'Approved',
                'assigned_to_user_id' => \$user->id,
                'actual_approver_id' => \$user->id,
                'approved_at' => now(),
                'comments' => 'Approved by ' . \$user->name,
            ]);

            if (\$paymentAdviceDetail->paymentAdvice) {
                \$this->recalculateTotals(\$paymentAdviceDetail->paymentAdvice);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Rincian Termin berhasil disetujui (Approved).');
        } catch (\Exception \$e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyetujui Rincian Termin: ' . \$e->getMessage());
        }
    }

    public function rejectDetail(Request \$request, ErpPaymentAdviceDetail \$paymentAdviceDetail)
    {
        \$user = auth()->user();

        DB::beginTransaction();
        try {
            \$activeApproval = \$paymentAdviceDetail->approvals()->where('status', 'Pending')->first();

            if (\$activeApproval) {
                \$isAuthorized = false;
                if (\$user->hasRole('superadmin')) {
                    \$isAuthorized = true;
                } elseif (\$activeApproval->assigned_to_user_id) {
                    if (\$user->id == \$activeApproval->assigned_to_user_id) {
                        \$isAuthorized = true;
                    }
                } elseif (\$activeApproval->assigned_to_role_id) {
                    \$hasRole = \Illuminate\Support\Facades\DB::connection('master')
                        ->table('role_user')
                        ->where('user_id', \$user->id)
                        ->where('role_id', \$activeApproval->assigned_to_role_id)
                        ->exists();
                    if (\$hasRole) {
                        \$isAuthorized = true;
                    }
                }

                if (!\$isAuthorized) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menolak termin ini.');
                }

                \$activeApproval->update([
                    'status' => 'Rejected',
                    'comments' => \$request->input('reason', 'Rejected by ' . \$user->name),
                    'actual_approver_id' => \$user->id,
                    'approved_at' => now(),
                ]);

                // Cancel subsequent steps
                \$paymentAdviceDetail->approvals()->where('status', 'Waiting')->update(['status' => 'Cancelled']);

                \$paymentAdviceDetail->update([
                    'approval_status' => 'Rejected'
                ]);

                DB::commit();
                return redirect()->back()->with('success', 'Rincian Termin berhasil ditolak.');
            }

            // FALLBACK RULES
            if (!\$user->hasRole('superadmin') && !\$user->hasRole('finance') && !\$user->hasRole('ceo')) {
                return redirect()->back()->with('error', 'Hanya Finance / CEO / Superadmin yang berhak menolak (Reject) Termin ini.');
            }

            \$paymentAdviceDetail->update([
                'approval_status' => 'Rejected'
            ]);

            \App\Models\Erp\ErpApproval::create([
                'payment_advice_detail_id' => \$paymentAdviceDetail->id,
                'level' => 1,
                'status' => 'Rejected',
                'assigned_to_user_id' => \$user->id,
                'actual_approver_id' => \$user->id,
                'approved_at' => now(),
                'comments' => \$request->input('reason', 'Rejected by ' . \$user->name),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Rincian Termin telah ditolak (Rejected).');
        } catch (\Exception \$e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menolak Termin: ' . \$e->getMessage());
        }
    }

    public function markPaidDetail(ErpPaymentAdviceDetail \$paymentAdviceDetail)
    {
        \$user = auth()->user();
        if (!\$user->hasRole('superadmin') && !\$user->hasRole('finance')) {
            return redirect()->back()->with('error', 'Hanya Tim Finance / Superadmin yang berhak memproses pembayaran termin.');
        }

        DB::beginTransaction();
        try {
            \$paymentAdviceDetail->update([
                'date_paid' => now() // Marking it paid
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Pembayaran termin berhasil dicatat.');
        } catch (\Exception \$e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mencatat pembayaran: ' . \$e->getMessage());
        }
    }
PHP;

$pattern = '/\s+public function submit\(.*?\n\s+public function destroy\(/s';
if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, "\n$replacement\n\n    public function destroy(", $content);
    file_put_contents($file, $content);
    echo "Successfully replaced methods in ErpPaymentAdviceController.\n";
} else {
    echo "Could not find methods block.\n";
}
