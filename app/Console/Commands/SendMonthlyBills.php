<?php
namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendMonthlyBills extends Command
{
    protected $signature = 'mess:send-bills {month_cycle}';
    protected $description = 'Send monthly WhatsApp bill to members';

    public function handle()
    {
        $cycle = $this->argument('month_cycle');

        $rows = DB::table('billings as b')
            ->join('members as m', 'm.id', '=', 'b.member_id')
            ->where('b.month_cycle', $cycle)
            ->where('m.is_active', 1)
            ->select('m.name', 'm.mobile_number', 'b.net_payable', 'b.due_date')
            ->get();

        $ok = 0;
        foreach ($rows as $r) {
            if (!$r->mobile_number) continue;
            $sent = WhatsAppService::sendTemplate(
                $r->mobile_number,
                config('whatsapp.bill_template'),
                [$r->name, number_format($r->net_payable), (string) $r->due_date]
            );
            if ($sent) $ok++;
            usleep(300000);
        }

        $this->info("Sent: {$ok} / " . $rows->count());
    }
}
