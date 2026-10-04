<?php
return [
    'token'    => env('WHATSAPP_TOKEN'),
    'phone_id' => env('WHATSAPP_PHONE_ID'),
    'bill_template' => env('WHATSAPP_BILL_TEMPLATE', 'mess_bill'),
];
