# Isbank Sanalpos Payment Gateway for Botble

Bu plugin Botble CMS uchun Isbank Sanalpos (İşbank Virtual POS) to'lov tizimini integratsiya qiladi.

## O'rnatish

1. Plugin papkasini `platform/plugins/isbank-sanalpos` ga joylashtiring.
2. Admin panelga kiring: `Platform → Plugins` va `Isbank Sanalpos` pluginini faollashtiring.
3. `Payment Methods` bo'limiga boring va kerakli ma'lumotlarni kiriting:
   - Client ID (Merchant ID)
   - API Username
   - API Password
   - Store Key (3D Secure Key)
   - Sandbox rejimini yoqing/o'chiring
4. Isbank merchant panelida callback URL sifatida quyidagini kiriting:
   `https://sizning-domeningiz.com/payment/isbank-sanalpos/callback`

## Ishlab chiqish holati

Bu hozircha skeleton plugin. Isbank'ning haqiqiy API chaqiruvlari va hash hisoblash algoritmi
Isbank rasmiy dokumentatsiyasi asosida to'ldirilishi kerak. Quyidagi joylar TODO:

- `src/Providers/HookServiceProvider.php` — checkout vaqtida formni yuborish va hash hisoblash.
- `src/Http/Controllers/IsbankSanalposController.php` — callback'ni qabul qilish va tasdiqlash (hash verification).
- `src/Services/Abstracts/IsbankSanalposPaymentAbstract.php` — refund API chaqiruvi.
