<?php

use MsTeknoloji\IsbankSanalpos\Http\Controllers\IsbankSanalposController;
use Illuminate\Support\Facades\Route;

Route::middleware('core')->group(function () {
    // Isbank bank sahifasidan POST bilan qaytadi — CSRF tekshiruvi o'chirilgan bo'lishi kerak.
    // VerifyCsrfToken middleware'da "payment/isbank-sanalpos/*" qo'shilishi lozim yoki
    // bu route'lar web middleware'siz ishlashi kerak.
    Route::match(['get', 'post'], 'payment/isbank-sanalpos/callback', [IsbankSanalposController::class, 'callback'])
        ->name('payments.isbank-sanalpos.callback');

    Route::match(['get', 'post'], 'payment/isbank-sanalpos/fail', [IsbankSanalposController::class, 'fail'])
        ->name('payments.isbank-sanalpos.fail');
});
