# Image widget with an auto WebP/AVIF file generation for Yii2 Framework with resizing for different HTML tags.

**ImgOpt** is an image optimization widget for [Yii2 Framework](https://www.yiiframework.com) with auto [WebP](https://developers.google.com/speed/webp) & [AVIF](https://caniuse.com/avif) image formats generation from `PNG` and `JPG` files.

Widget forked from [pelock/yii2-imgopt](https://www.yiiframework.com/extension/pelock/yii2-imgopt). The main addition to the original widget is the possibility of automatic resizing images for different screen extensions and for different HTML tags.

## How to make my website faster?

A frequent cause of low `PageSpeed ​​Insights` scores is the use of heavy PNG/TGU images, so if you want to optimize image loading, you should use WebP & AVIF image formats. The use of modern formats saves up to 30% of the file size without significant loss of quality.

The automatic image generation process eliminates the need to manually use an image conversion tool, upload new WebP/AVIF images to the server, and update your HTML code.

## Automate PNG & JPG to WebP & AVIF conversion

I have decided to create a Yii2 widget that would automate this task.

What it does? Instead of static images like this:

```html
<img src="/images/product/extra.png" alt="Extra product">
```

it will automatically generate an extra image in new [WebP](https://developers.google.com/speed/webp) format (in the same directory, where the provided image is located) and serve it to your browser in HTML `<picture>` tag, with a default fallback to the original image for browsers that don't support WebP images.

Replace your `IMG` tag within your `HTML` templates with a call to:

```php
<?= \PELock\ImgOpt\ImgOpt::widget(["src" => "/images/product/extra.png", "alt" => "Extra product" ]) ?>
```

(Image path is relative to [Yii2 Framework @webroot alias](https://www.yiiframework.com/wiki/667/yii-2-list-of-path-aliases-available-with-default-basic-and-advanced-app))

And once run, the widget code will generate a new WebP & AVIF image files on the fly (original image is left **untouched**) and he following HTML code gets generated:

```html
<picture>
    <source type="image/avif" srcset="/images/product/extra.avif">
    <source type="image/webp" srcset="/images/product/extra.webp">
    <img src="/images/product/extra.png" alt="Extra product">
</picture>
```

The browser will pick up the best source for the provided image, and thanks to revolutionary WebP and AVIF compression, it will make your website loading faster.

## Image lazy-loading

[Lazy images loading](https://web.dev/browser-level-image-lazy-loading/) makes the browser load the images when it reach a certain point, after which the image became visible in the current browser tab. You can use this pure HTML feature (no JS involved) from within the widget:

```php
<?= \PELock\ImgOpt\ImgOpt::widget(["src" => "/images/product/extra.png", "loading" => "lazy" ]) ?>
```

The generated output looks like this:

```html
<picture>
    <source type="image/avif" srcset="/images/product/extra.avif">
    <source type="image/webp" srcset="/images/product/extra.webp">
    <img src="/images/product/extra.png" loading="lazy">
</picture>
```

Use it to make your website loading times even faster.

## AVIF image generation

ImgOpt will automatically generate AVIF file (when `type_src` param has value picture_avif/srcset_avif/background_avif) if it's supported by the existing PHP installation. If the conversion function is not available, it will just skip this step.

PHP has had AVIF support in its GD extension since PHP version 8.1

Noticed that the generation time of an AVIF picture is slightly longer than that of WebP.

## Automatic WebP/AVIF generation for updated images

ImgOpt will set the modification date of the generated WebP image to match the modification date of the original image file.

If ImgOpt detects that a file modification date of the source image file is different than the date of the previously generated WebP image file - it will automatically re-create the new WebP image file!

## Installation

The preferred way to install the library is through the [composer](https://getcomposer.org/).

Run:

```
php composer.phar require --prefer-dist rhladun/yii2-imgopt "*"
```

Or add:

```
"rhladun/yii2-imgopt": "*"
```

to the`require` section within your `composer.json` config file.

The installation package is available at https://packagist.org/packages/rhladun/yii2-imgopt

Source code is available at [https://github.com/rhladun/yii2-imgopt](https://github.com/rhladun/yii2-imgopt/)

## Image quality

By default the conversion tries all the steps from 100% output image quality down to 70% to generate the WebP file that is smaller than the original image.

## Disable WebP/AVIF images serving

If for some reason you want to disable WebP/AVIF file serving, you can do it per widget settings:

```php
<?= \rhladun\ImgOpt\ImgOpt::widget(["src" => "/images/product/extra.png", "alt" => "Extra product", 'type_src'=>'srcset_webp', "disable" => true ]) ?>
```

## Recreate WebP/AVIF files

The widget code automatically detects if there's a WebP/AVIF images in the directory with the original image. If it's not there - it will recreate them. It's only done once.

If you wish to force the widget code to recreate it anyway, pass the special param to the widget code:

```php
<?= \rhladun\ImgOpt\ImgOpt::widget(["src" => "/images/product/extra.png", "alt" => "Extra product", "type_src"=>"picture_avif", "recreate" => true ]) ?>
```

You might want to recreate all of the WebP and AVIF files and to do that without modifying, change the widget source code from:

```php
/**
 * @var bool set to TRUE to recreate *ALL* of the WebP and AVIF files again (optional)
 */
const RECREATE_ALL = false;
```

to:

```php
/**
 * @var bool set to TRUE to recreate *ALL* of the WebP files again (optional)
 */
const RECREATE_ALL = true;
```


