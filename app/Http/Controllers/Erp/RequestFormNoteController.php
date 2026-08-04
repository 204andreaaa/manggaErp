<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Erp\RequestForm;

class RequestFormNoteController extends Controller
{
    public function storeNote(Request $request, RequestForm $requestForm)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $requestForm->notesAttachments()->create([
            'type' => 'note',
            'content' => $request->input('content'),
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    public function storeAttachment(Request $request, RequestForm $requestForm)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('attachments', $fileName, 'public');

            $requestForm->notesAttachments()->create([
                'type' => 'attachment',
                'file_path' => $path,
                'file_name' => $fileName,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Attachment uploaded successfully.');
        }

        return redirect()->back()->with('error', 'Failed to upload attachment.');
    }
}
