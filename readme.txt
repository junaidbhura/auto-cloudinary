=== Auto Cloudinary ===
Contributors: junaidbhura
Tags: cloudinary, dynamic-images, cdn, image-optimization, image-manipulation
Requires at least: 4.4
Tested up to: 5.2
Requires PHP: 5.6
Stable tag: 1.1.1

Super simple Cloudinary auto-upload implementation for WordPress.

== Description ==

[Check out the Github Repository ♥](https://github.com/junaidbhura/auto-cloudinary)

This plugin provides a **super simple** [Cloudinary auto-upload](https://cloudinary.com/documentation/fetch_remote_images#auto_upload_remote_resources) implementation for WordPress.

It is inspired by [Photon](https://developer.wordpress.com/docs/photon/) and [Tachyon](https://github.com/humanmade/tachyon-plugin).

Cloudinary will **automatically fetch and serve the images** from your media library like a **CDN**, without you having to worry about the complicated upload API! Just [set up auto-upload](https://github.com/junaidbhura/auto-cloudinary/wiki/Setup) in your Cloudinary settings, enter the details in the plugin's options, and you're all set!

Easy peasy 😎

### Quick Links

[Setup](https://github.com/junaidbhura/auto-cloudinary/wiki/Setup) | [Issues](https://github.com/junaidbhura/auto-cloudinary/issues) | [Functions](https://github.com/junaidbhura/auto-cloudinary/wiki/Functions) | [Filters](https://github.com/junaidbhura/auto-cloudinary/wiki/Filters) | [Best Practices](https://github.com/junaidbhura/auto-cloudinary/wiki/Best-Practices)

## Why did you build this plugin?

There already is an **official Cloudinary plugin** available. But in my opinion, it's a bit of an overkill and takes over the admin UI. This plugin aims to be:

* **Super simple** and light-weight
* Totally seamless and **out of the way**
* A flexible tool for **WordPress developers**

## What is Cloudinary Auto-Upload?

Cloudinary gives you two options to upload files to it's servers:

1. The complicated **Upload API** 😱
2. The super easy and magical **Fetch API** 🎩

#### Upload API

_TL;DR: Too complicated and in the way_ 👎

Cloudinary gives you an API, using which, you can manually upload the images to Cloudinary. So you'd need an **API key**, etc. The **official plugin** uses this method. When you upload an image to the media library, it in turn, uploads it to Cloudinary. This could be a problem if you have thousands of **existing images**, and might not be flexible enough to support **custom architecture**.

#### Fetch API

_TL;DR: Magical_ 👍

This plugin uses the super easy **Auto-Upload** feature in the **Fetch API**. We just tell Cloudinary where to find the files on our server (or on S3 or anywhere on the Internet), and it **automatically downloads** it from there and saves it on to it's servers the **first time you ask for it**, like a CDN would!

## What does this plugin do?

This plugin does two main things:

1. Provides a simple function `cloudinary_url()` to get a Cloudinary auto-upload URL for any image in your media library, with all the Cloudinary transformations, so you can **dynamically manipulate an image on the fly**.
2. Attempts to automatically convert all image URLs on the front-end into a Cloudinary auto-upload URL, so you can **use Cloudinary as an image CDN**.

## The magical function 🎩

**`cloudinary_url( $identifier, $args )`**

This function returns a Cloudinary Auto Upload URL for an image. Please read the [Best Practices](https://github.com/junaidbhura/auto-cloudinary/wiki/Best-Practices) page before using this.

#### Parameters

* **identifier** (integer/string)(required) : Either the ID of the attachment, or a full image URL.
* **args** (array)(optional) : Arguments to manipulate the image.

#### Return Value

Returns a URL (string):

`
'https://res.cloudinary.com/cloud-name/auto-mapping-folder/2017/12/your-image.jpg'
`

#### Arguments

You can optionally send an array of arguments which can transform the image, and set a dynamic file name. Ex:

`
array(
	'transform' => array( // Optional. All transformations go here.
		'width'   => 300,
		'height'  => 200,
		'crop'    => 'fill',
		'quality' => '80',
		'gravity' => 'face',
	),
	'file_name' => 'whatever-file-name-you-want', // Optional. If you want to use a dynamic file name for SEO. Don't use the file extension!
);
`

Here's a [full list of transformations](https://cloudinary.com/documentation/image_transformations) you can achieve with Cloudinary.

### Examples

`
<?php
$url_1 = cloudinary_url( 123, array(
	'transform' => array(
		'width'   => 300,
		'height'  => 200,
		'crop'    => 'fill',
		'quality' => '80',
		'gravity' => 'face',
	),
	'file_name' => 'dynamic-file-name',
) );

$url_2 = cloudinary_url( 'https://www.yourwebsite.com/wp-content/uploads/2017/12/my-image.jpg', array(
	'transform' => array(
		'width'   => 100,
		'height'  => 100,
	),
) );

// $url_1 : https://res.cloudinary.com/cloud-name/images/w_300,h_200,c_fill,q_80,g_face/auto-mapping-folder/2017/12/my-image/dynamic-file-name.jpg
// $url_2 : https://res.cloudinary.com/cloud-name/w_100,h_100/auto-mapping-folder/2017/12/my-image.jpg
?>

<img src="<?php echo esc_url( $url_1 ); ?>" width="300" height="200" alt="">
<img src="<?php echo esc_url( $url_2 ); ?>" width="100" height="100" alt="">
`

== Installation ==

Upload 'auto-cloudinary' to the '/wp-content/plugins/' directory.

Activate the plugin through the 'Plugins' menu in WordPress.

== Screenshots ==

1. WordPress Options
2. Cloudinary Cloud Name
3. Cloudinary Auto Upload Setup

== Changelog ==

= 1.1.1 =
* Better AJAX support [#13](https://github.com/junaidbhura/auto-cloudinary/issues/13)

= 1.1.0 =
* Added default crop options to the WP Admin [#10](https://github.com/junaidbhura/auto-cloudinary/issues/10)
* Full release details: [https://github.com/junaidbhura/auto-cloudinary/releases/tag/1.1.0](https://github.com/junaidbhura/auto-cloudinary/releases/tag/1.1.0)

= 1.0.3 =
* New filters for default hard and soft crops [#2](https://github.com/junaidbhura/auto-cloudinary/issues/2). Props [@petersplugins](https://github.com/petersplugins)
* Performance improvements
* Full release details: [https://github.com/junaidbhura/auto-cloudinary/releases/tag/1.0.3](https://github.com/junaidbhura/auto-cloudinary/releases/tag/1.0.3)

= 1.0.2 =
* Remove empty width and height from URL [#1](https://github.com/junaidbhura/auto-cloudinary/issues/1)

= 1.0.1 =
* Add default crop to replaced content images.

= 1.0.0 =
* First stable release.
