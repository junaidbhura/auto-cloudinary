# Cloudinary Auto-Upload for WordPress

This plugin provides a **super simple** [Cloudinary auto-upload](https://cloudinary.com/documentation/fetch_remote_images#auto_upload_remote_resources) implementation for WordPress.

It is inspired by [Photon](https://developer.wordpress.com/docs/photon/) and [Tachyon](https://github.com/humanmade/tachyon-plugin).

Cloudinary will **automatically fetch and serve the images** from your media library like a **CDN**, without you having to worry about the complicated upload API! Just [set up auto-upload](https://github.com/junaidbhura/auto-cloudinary/wiki/Setup) in your Cloudinary settings, enter the details in the plugin's options, and you're all set!

Easy peasy 😎

## Why did you build this plugin?

There already is an **official Cloudinary plugin** available. But in my opinion, it's a bit of an overkill and takes over the admin UI. This plugin aims to be:

* **Super simple** and light-weight
* Totally seamless and **out of the way**
* A flexible tool for **WordPress developers**

## What is Cloudinary Auto-Upload?

Cloudinary gives you two options to upload files to it's servers:

1. The complicated **Upload API** 😱
2. The super easy and magical **Fetch API** 🎩

### Upload API

_TL;DR: Too complicated and in the way_ 👎

Cloudinary gives you an API, using which, you can manually upload the images to Cloudinary. So you'd need an **API key**, etc. The **official plugin** uses this method. When you upload an image to the media library, it in turn, uploads it to Cloudinary. This could be a problem if you have thousands of **existing images**, and might not be flexible enough to support **custom architecture**.

### Fetch API

_TL;DR: Magical_ 👍

This plugin uses the super easy **Auto-Upload** feature in the **Fetch API**. We just tell Cloudinary where to find the files on our server (or on S3 or anywhere on the Internet), and it **automatically downloads** it from there and saves it on to it's servers the **first time you ask for it**, like a CDN would!

## What does this plugin do?

This plugin does two main things:

1. Provides a simple function `cloudinary_url()` to get a Cloudinary auto-upload URL for any image in your media library, with all the Cloudinary transformations, so you can **dynamically manipulate an image on the fly**.
1. Attempts to automatically convert all image URLs on the front-end into a Cloudinary auto-upload URL, so you can **use Cloudinary as an image CDN**.

## The magical function 🎩

#### `cloudinary_url( $identifier, $args )`

This function returns a Cloudinary Auto Upload URL for an image. Please read the [Best Practices](https://github.com/junaidbhura/auto-cloudinary/wiki/Best-Practices) page before using this.

**Parameters**

* **identifier** (integer/string)(required) : Either the ID of the attachment, or a full image URL.
* **args** (array)(optional) : Arguments to manipulate the image.

**Return Value**

Returns a URL (string):

```php
'https://res.cloudinary.com/cloud-name/auto-mapping-folder/2017/12/your-image.jpg'
```

**Arguments**

You can optionally send an array of arguments which can transform the image, and set a dynamic file name. Ex:

```php
array(
	'transform' => array(
		'width'   => 300,
		'height'  => 200,
		'crop'    => 'fill',
		'quality' => '80',
		'gravity' => 'face',
	),
	'file_name' => 'whatever-file-name-you-want',
);
```

Here's a [full list of transformations](https://cloudinary.com/documentation/image_transformations) you can achieve with Cloudinary.

#### Examples:

```php
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
?>

<img src="<?php echo esc_url( $url_1 ); ?>" width="300" height="200" alt="">
<img src="<?php echo esc_url( $url_2 ); ?>" width="100" height="100" alt="">
```

## Documentation

The Wiki contains all the documentation for this plugin: [Documentation](https://github.com/junaidbhura/auto-cloudinary/wiki)
