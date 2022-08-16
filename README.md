# Dynamic Cloundary

__Automatically serve all your images optimized from the cloud.__

This WordPress plugin proxies all images and videos on a page through [Cloudinary](https://cloudinary.com/). It uses [Cloudinary auto-upload](https://cloudinary.com/documentation/fetch_remote_images#auto_upload_remote_resources) functionality so Cloudinary uploads and serves images on the fly. This means you don't have to upload anything to Cloudinary. Everything just works out of the box. Cloudinary also provides a powerful transformation API so you can modify and optimize images on the fly.

## Install / Setup

1. Upload and active the WordPress plugin. Make sure you run `composer install`.
2. Create a [Cloudinary](https://cloudinary.com) account. Make sure you take name of your `Cloud Name`. Also, you need to set up your auto upload mapping. This can be done in the Settings > Upload section of your Cloudinary Dashboard. Take close note of the name you give your folder.

![Auto mapping cloudianry](./screenshots/auto-mapping.png)

3. Configure the plugin in the WordPress admin in `Settings > Dynamic Cloudinary`

## Issues

If you identify any errors or have an idea for improving the plugin, please [open an issue](https://github.com/tlovett1/wp-media-pro/issues?state=open).

## License

WP Media Pro is free software; you can redistribute it and/or modify it under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
