# Dynamic Cloundary

__Automatically serve all your images optimized from the cloud.__

This WordPress plugin proxies all images and videos on a page through [Cloudinary](https://cloudinary.com/). It uses [Cloudinary auto-upload](https://cloudinary.com/documentation/fetch_remote_images#auto_upload_remote_resources) functionality so Cloudinary uploads and serves images on the fly. This means you don't have to upload anything to Cloudinary. Everything just works out of the box. Cloudinary also provides a powerful transformation API so you can modify and optimize images on the fly.

## Install / Setup

1. Create a [Cloudinary](https://cloudinary.com) account. Make sure you take name of your `Cloud Name`. Also, you need to set up your auto upload mapping. This can be done in the Settings > Upload section of your Cloudinary Dashboard. Take close note of the name you give your folder.

![Auto mapping Cloudinary](./.github/assets/auto-mapping.png)
2. Upload and activate the WordPress plugin. Make sure you run `composer install`.
3. Configure the plugin in the WordPress admin in `Settings > Dynamic Cloudinary`. For `Auto Mapping Folder`, I'd provide `folder-name` from the screenshot above.

## Cloudinary API

Cloudinary lets you [customize images and video](https://cloudinary.com/documentation/image_transformations) on the fly using their API. Dynamic Cloudinary supports many of these transformations. In order to add a transformation to an image, you can add a `data-` attribute to the element. For example, to modify the image crop you would add something like `data-crop="fill"` to the image element. To modify image opacity, you would add something like `data-opacity="50"` to the image element. Check out the full list of supported transformations:

```
format
angle
aspect_ratio
background
border
crop
color
dpr
duration
effect
end_offset
flags
height
overlay
opacity
quality
radius
start_offset
named_transformation
underlay
video_codec
width
x
y
zoom
audio_codec
audio_frequency
bit_rate
color_space
default_image
delay
density
fetch_format
gravity
prefix
page
video_sampling
progressive
```

You can also add `data-transformations-string` to any image or source element for manually specifying the transformation string.

## Known Issues

* Any HTML entities inside of an image or source element will prevent this plugin from replacing the URL e.g. having `&amp;` in an `alt` tag.

## Issues

If you identify any errors or have an idea for improving the plugin, please [open an issue](https://github.com/tlovett1/dynamic-cloudinary/issues?state=open).

## License

Dynamic Cloudinary is free software; you can redistribute it and/or modify it under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
