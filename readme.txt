=== Dynamic Cloudinary ===
Contributors: tlovett1
Tags: images, cdn, cloudinary, core web vitals, cwv, performance, image optimization
Requires at least: 5.5
Tested up to: 6.2
Stable tag: 1.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically serve all your images optimized from the cloud.

== Description ==

This WordPress plugin proxies all images and videos on a page through [Cloudinary](https://cloudinary.com/). It uses [Cloudinary auto-upload](https://cloudinary.com/documentation/fetch_remote_images#auto_upload_remote_resources) functionality so Cloudinary uploads and serves images on the fly. This means you don't have to upload anything to Cloudinary. Everything just works out of the box. Cloudinary also provides a powerful transformation API so you can modify and optimize images on the fly.
Pull requests are welcome on [Github](https://github.com/tlovett1/dynamic-cloudinary).

*Note:* This plugin requires a [Cloudinary](https://cloudinary.com/) account to serve images. Please refer to Cloudinary [terms of service](https://cloudinary.com/tos) and [privacy policy](https://cloudinary.com/privacy).

== Installation ==

See installation instructions on [Github](http://github.com/tlovett1/dynamic-cloudinary).


== Support ==

For full documentation, questions, feature requests, and support concerning the Dynamic Cloudinary plugin, please refer to [Github](http://github.com/tlovett1/dynamic-cloudinary).

== Changelog ==

= 1.3.0 =
* Add `dc_cloudinary_exclude` filter and support for `data-cloudinary-exclude` attribute. If either is provided, the asset will be excluded from Cloudinary parsing.

= 1.2.3 =
* Better process transformation arguments

= 1.2.1 =
* Skip images that have the ?bypass_cloudinary parameter

= 1.1.7 =
* Fix bug breaking relative/absolute urls

= 1.1.6 =
* Docs and plugin settings link

= 1.1.4 =
* Typo fix

= 1.1.3 =
* Plugin released to .org

