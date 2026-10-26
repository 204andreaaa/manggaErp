<?php

namespace Tests\Feature\Erp\MasterData;

use Illuminate\Testing\TestResponse;

/**
 * Shared helpers for the master-data screen tests.
 */
trait MasterDataHelpers
{
    /**
     * Call a server-side DataTables endpoint the way the page's JS does.
     */
    protected function datatable(string $route, string $search = '', int $orderColumn = 0, string $dir = 'asc', string $method = 'post'): TestResponse
    {
        $params = [
            'draw' => 3,
            'start' => 0,
            'length' => 50,
            'search' => ['value' => $search, 'regex' => 'false'],
            'order' => [['column' => $orderColumn, 'dir' => $dir]],
        ];

        return $method === 'get'
            ? $this->getJson(route($route, $params))
            : $this->postJson(route($route), $params);
    }

    /**
     * A known application bug: the test body below documents the correct
     * behaviour. Set RUN_KNOWN_BUGS=1 to run the body anyway (it should fail
     * until the bug is fixed; then remove the skip).
     */
    protected function skipKnownBug(string $message): void
    {
        if (!getenv('RUN_KNOWN_BUGS')) {
            $this->markTestSkipped('BUG: '.$message);
        }
    }

    /**
     * After HTML-decoding every onclick attribute in `$html`, the payload's
     * quote must still be escaped (\') — otherwise the stored text breaks out
     * of the JS string and runs.
     */
    protected function assertOnclickDoesNotExecute(string $html, string $payload): void
    {
        preg_match_all('/onclick="([^"]*)"/', $html, $m);
        $this->assertNotEmpty($m[1], 'No onclick attribute found in the row HTML.');

        $breakout = '/(?<!\\\\)'.preg_quote($payload, '/').'/';
        foreach ($m[1] as $attr) {
            $js = html_entity_decode($attr, ENT_QUOTES | ENT_HTML5);
            $this->assertDoesNotMatchRegularExpression($breakout, $js, "onclick runs injected code: {$js}");
        }
    }
}
