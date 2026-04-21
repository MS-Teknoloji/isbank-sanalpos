# Isbank Sanalpos Payment Gateway for Botble

This plugin integrates **Isbank Sanalpos** (Türkiye İş Bankası Virtual POS) as a payment gateway
into the Botble CMS eCommerce system. It allows your customers to pay with Visa / MasterCard
through Isbank's **3D Secure** (3D_Pay) infrastructure built on the Asseco / NestPay platform.

![Isbank Sanalpos](./screenshot.png)

## Features

* **3D Secure (3D_Pay model)** — payments are authenticated through the cardholder's bank
  before the capture step, reducing chargeback risk.
* **Sandbox & Production modes** — switch between the shared Asseco test gateway and the
  Isbank production gateway with a single toggle.
* **Multi-currency support** — TRY, USD, EUR, GBP, JPY and RUB are mapped to the correct
  ISO 4217 numeric codes expected by the gateway.
* **User-friendly error messages** — ISO-8583 `ProcReturnCode` and 3D Secure `mdStatus`
  values are translated into readable messages (card declined, insufficient funds, expired
  card, 3D authentication failed, etc.) instead of raw technical text.
* **Hash verification** — incoming bank callbacks are validated using the
  `HASHPARAMSVAL + StoreKey` SHA1 scheme, so forged requests are rejected.
* **CSRF-safe callback** — the callback and fail routes are excluded from CSRF protection
  because Isbank posts back from outside the user session; a checkout token is carried
  in the URL to restore the shopper's context.
* **Order history details** — successful payments store `auth_code`, `host_ref_num`,
  `trans_id`, `masked_pan` and `proc_return_code` in the payment metadata for later
  reference.

## Requirements

* Botble core **7.0.0** or later
* The **Payment** plugin (`botble/payment`) enabled
* The **Ecommerce** plugin enabled (recommended)
* PHP 8.1+

## Installation

### Install manually

1. Upload the plugin folder to `platform/plugins/isbank-sanalpos`.
2. Go to **Admin Panel → Platform → Plugins** and click **Activate** on the
   *Isbank Sanalpos* plugin.
3. Go to **Settings → Payment Methods** and open *Isbank Sanalpos*.
4. Fill in:
   * **Client ID (Merchant ID)** — provided by Isbank
   * **Store Key (3D Secure Key)** — provided by Isbank
   * **Sandbox** — leave enabled while testing, disable for production

### Sandbox credentials

For quick testing you can use the shared Asseco test credentials:

* Client ID: `700655000200`
* Store Key: `TRPS0200`

Test cards:

* Visa: `4508 0345 0803 4509`
* MasterCard: `5406 6754 6466 4658`
* Expiry: `12/26`, CVV: `000`, 3D password: `a`

## Gateway URLs

These are configured automatically based on the Sandbox toggle; no action is required
on your side:

* **Sandbox:** `https://entegrasyon.asseco-see.com.tr/fim/est3Dgate`
* **Production:** `https://sanalpos.isbank.com.tr/servlet/est3Dgate`

## Callback URLs

The `okUrl` and `failUrl` are sent inside the payment form on every transaction, so there
is nothing to configure on the Isbank merchant panel. For reference:

* Success callback: `https://your-domain.com/payment/isbank-sanalpos/callback`
* Failure callback: `https://your-domain.com/payment/isbank-sanalpos/fail`

Both routes are whitelisted in `app/Http/Middleware/VerifyCsrfToken.php` so that POST
requests coming from Isbank are accepted.

## Troubleshooting

### Blank page on the Isbank 3D Secure gateway (production)

A blank response from `https://sanalpos.isbank.com.tr/servlet/est3Dgate` is almost always
caused by merchant-side configuration rather than by this plugin. Check with your Isbank
integration contact that:

1. Your merchant profile is activated for the **3D_Pay** model (not plain 3D).
2. The server IP address sending the payment request is whitelisted.
3. The Store Key you entered matches the one on the Isbank panel exactly.

### Hash verification fails in sandbox

The sandbox Asseco gateway sometimes omits the `HASH` / `HASHPARAMSVAL` fields. When
Sandbox mode is on, missing hash fields are treated as valid to let you finish a test
checkout; in production a missing hash is always rejected.

## Security

If you discover any security related issues, please open a private issue on the
[repository](https://github.com/MS-Teknoloji/isbank-sanalpos) instead of disclosing
them publicly.

## Credits

* [MS Teknoloji](https://github.com/MS-Teknoloji)
* [Firdavs](https://github.com/Firdavs9512)

## License

The MIT License (MIT). Please see the [License File](LICENSE) for more information.
