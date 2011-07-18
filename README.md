# P3
Long story short - Ive always been a PHP fan ever since I started developing.  I did, however, switch to the RoR world for a while - Falling in love with the MVC paradigm shortly thereafter, of course.

Though I love Rails, I just found myself missing PHP the entire time.  Coming back though, I *definitely* wanted to implement MVC, and more particularly follow DRY coding standards.  There are tons of great MVC framework choices out there, and there's no way I'd even try to claim to be the best.  I can, however, encourage you to take a look and see what you think. 

P3 has been a work in progress for a little over two years now.  It was orignally called EEF, which spawned from some inspiring ideas I got from [BigE](https://github.com/BigE)'s [SiTech](https://github.com/BigE/SiTech) library at the time.  I wasn't happy with my ending result, but I sure did learn a lot about the MVC paradigm during my first go at it.  I decided to go for a gradual, but complete rewrite.  P3 being my ending result.

I currently use P3 in 2 production apps for my employer, and am developing 3 personally on the side as I enhance P3.  That being said, I work on this thing *a lot*.  So please check back for updates!

Features
--------
* Fully Customizable MVC Restful Routing
* Advanced Model Relations  (belongs-to, has-one, has-many, and even has-many-through)
* Views w/ support for "partials"
* Nested/Namespaced Controller support
* PDO Database  (currently only fully supporting MySQL. Postgres shortly.  Feel free to help!)
* Form/Html Helpers to avoid the mondain tasks we have all grown to hate
* Options scattered litteraly everywhere to modify P3s behavior, and even only use bits and pieces if you so chose.
* Helpful Documentation 

Changelog
--------
<b>v1.1.5</b>

* Added default_url option to ActiveRecord\Attachments
* Added getJSON to collections
* Improved ActiveRecord::getData() function to use send(), allowing for methods to be called
* Fixed exceptions being thrown by ActiveRecord\Attachments, if the directory went missing from outside P3
* ActiveRecord\Attachments clean up after themselves a lot nicer
* New str::phone() helper method
* Acronyms now usable in model names (fixed bug in camel case to underscore conversion)
* Fixed various other minor bugs


<b>v1.1.4</b>

* Added easy pagination rendering to existing pagination support

<b>v1.1.3</b>

* Finished remaining functionality of paginized collections
* Cleaned and refactored a couple parts of the library
* Fixed a couple of bugs

<b>v1.1.2</b>

* Attachments now clean old files out of attachment directories on record updates.  This was overlooked in the last release

<b>v1.1.1</b>

* Various bug fixes
* New handling of Model Attachments - now identical to RoR's AWESOME paperclip plugin

<b>v1.1.0</b>

* Various bug fixes
* Optimized Collections
* PayPal Merchant Integration
* Added new XML Builder Class
* Added new HTTP Client Class

## Getting Started
Getting started is as easy as:

1) Click GitHub's "Downloads" link above

2) Dowload the latest version

3) Extract the tarball

4) Start coding!

## Shoutout to the Rails Community
I still use RoR for several apps at work and gain ideas from the libary to this day.  I try to keep P3 as seemless of a switch from Rails as possible, as you will notice throughout.  I'm not aiming to recreate the wheel entirely here, just mimic the Rails way the best I can with my own bit of flavor.


## Special thanks to BigE
Check out his [SiTech](https://github.com/BigE/SiTech) library.  Especially if you are the type of coder that doesn't like to be locked into a framework, but still have a powerful library sitting behind your apps.
