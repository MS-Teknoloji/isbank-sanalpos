<?php

return [
    'client_id' => 'Client ID (Merchant ID)',
    'store_key' => 'Store Key (3D Secure Key)',
    'sandbox' => 'Sandbox / Test mode?',
    'instructions' => [
        'step_1' => 'Register a merchant account on :name',
        'step_2' => 'After registration you will receive Client ID and Store Key. For sandbox testing, either use the public Asseco test credentials (Client ID: 700655000200, Store Key: TRPS0200) or request sandbox credentials from Isbank integration team.',
        'step_3' => 'Enter the credentials into the box on the right hand side. Toggle "Sandbox" to switch between the Asseco test gateway and the Isbank production gateway.',
        'step_4' => 'Callback URL is sent automatically inside the payment form (okUrl / failUrl) on every transaction — no configuration needed on the Isbank panel. For reference, the URLs are:',
        'sandbox_note' => 'Sandbox mode uses the Asseco shared test gateway (https://entegrasyon.asseco-see.com.tr/fim/est3Dgate). Production uses https://sanalpos.isbank.com.tr/servlet/est3Dgate. Your Isbank production credentials will not work in sandbox — you need separate test credentials.',
        'test_cards' => 'Test cards for sandbox: 4508 0345 0803 4509 (Visa) or 5406 6754 6466 4658 (MasterCard) — expiry 12/26, CVV 000, 3D password "a".',
    ],
];
