<?php

namespace Tests\Feature\Erp\MasterData;

use Tests\ErpTestCase;

class PaymentTermTest extends ErpTestCase
{
    public function test_payment_terms_follow_their_own_permission_keys(): void
    {
        $this->actingInProject($this->makeUser('staff'));
        $this->get(route('erp.payment-terms.index'))->assertForbidden();

        // Granted from the Roles screen as payment_terms.* (config/menu.php).
        $this->actingInProject($this->makeUser('term_viewer', ['payment_terms.view']));
        $this->get(route('erp.payment-terms.index'))->assertOk();
    }
}
