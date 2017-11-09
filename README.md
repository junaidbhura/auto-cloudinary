# Cloudinary Auto-Upload for WordPress

This plugin provides a super simple [Cloudinary auto-upload](https://cloudinary.com/documentation/fetch_remote_images#auto_upload_remote_resources) implementation for WordPress.

Cloudinary will **automatically fetch and serve the images** from your media library, without you having to worry about the complicated fetch API! Just [set up auto-upload](https://github.com/junaidbhura/auto-cloudinary/wiki/Setup) in your Cloudinary settings, enter the details in the plugin's options, and you're all set!

Easy peasy 😎

## What does this plugin do?

This plugin does two main things:

1. Provides a simple function `cloudinary_url()` to get a Cloudinary auto-upload URL for any image in your media library, with all the Cloudinary transformations, so you can **dynamically manipulate an image on the fly**.
1. Attempts to automatically convert all image URLs on the front-end into a Cloudinary auto-upload URL, so you can **use Cloudinary as an image CDN**.

## The magical function 🎩

#### `cloudinary_url( $identifier, $args )`

**Parameters**

* **identifier** (integer/string)(required) : Either the ID of the attachment, or a full image URL.
* **args** (array)(optional) : Arguments to manipulate the image.

**Return Value**

Returns a URL (string):

```php
'https://res.cloudinary.com/cloud-name/auto-mapping-folder/2017/12/your-image.jpg'
```

**Arguments**

Here are some sample arguments you can use:

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
