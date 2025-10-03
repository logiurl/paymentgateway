=== UPI TimePay ===
Contributors: cursor
Tags: upi, payments, qr, tesseract, ocr
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 0.1.0
License: GPLv2 or later

Time-limited UPI QR + button, screenshot OCR with Tesseract.js, and admin confirmation workflow.

== Description ==
A simple UPI payment helper: renders a QR and UPI deeplink that expire after N seconds, lets users upload a screenshot and extracts a transaction ID with Tesseract.js, then stores a pending payment for admins to confirm.

Shortcodes:
[upi_timepay amount="199" ref="ORD123" expires="900" note="Order #123"]
[upi_timepay_review]

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Configure UPI ID and Payee Name under Settings > UPI TimePay.

== Changelog ==
= 0.1.0 =
* Initial release
