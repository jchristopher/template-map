This is a WordPress plugin, [Official download available on WordPress.org](http://wordpress.org/plugins/template-map/)

# Template Map

Automagic mapping of Page Templates to post IDs to facilitate better dynamic link generation and deciphering context

## Philosophy

Template Map is a utility plugin designed to make the creation and management of navigation elements in your custom theme that much easier. It abstracts the need to hard code post IDs and the like from your global and header navigation systems (if you choose to not use Menus) for instance.

It's based on the notion that when Pages dictate the base URI structure of a site, you can utilize each Page's Page Template to decipher post IDs that otherwise may have been hard coded into your theme. Hard coding IDs works fine during intial development, *but it's a maintenance nightmare*. As new site sections are added, the post IDs in your development environment quickly diverge from those in production and vice versa. Template Map aims to solve that problem and in doing so provide even more utility.

Naturally Pages may not encompass the entire sitemap of your site, which may have top level Custom Post Type slugs or Pages without a unique Page Template. While that's uncommon for my client work specifically, there are methods in Template Map that allow you to 'register' additional map entries as well.

## Documentation

Many times you may use something like the following to build your main site navigation:

```php
<nav>
  <ul>
    <li>
      <a href="<?php echo get_permalink( 83 ); ?>">About</a>
    </li>
  </ul>
</nav>
```

That works great during initial development, and continues to hold up when you migrate to production en masse for the first time. But what about when you're making subsequent updates and the post IDs in your development environment are different than those in production? You could take the time to sync the databases each and every time, but that's a bit overkill. Here's where Template Map comes in to play:

```php
<nav>
  <ul>
    <li>
      <?php $about_page_id = TemplateMap()->get_id_from_template( 'template-about.php' ); ?>
      <a href="<?php echo get_permalink( $about_page_id ); ?>">About</a>
    </li>
  </ul>
</nav>
```

Template Map allows you to *dynamically* retreive your desired post ID based on the Page Template you told it to use. Page Template filenames rarely (if ever) change, so it's the core concept of Template Map's implementation. Everything works backwards from here.

Naturally this philosophy assumes your Page Template is used only once, it's important to keep that in mind. Modern client sites often use unique Page Templates for the 'parent' pages of each site section. 

### Current Site Section

Template Map also makes it easier to determine whether the current page is within a site 'section' which is defined as a top level Page with a unique Page Template that has any number of child Pages and/or Custom Post Types within it. This is very useful when trying to set a 'current' state in your navigation, for example:

```php
<nav>
  <ul>
    <li class="<?php if( TemplateMap()->maybe_in_section( 'template-about.php' ) ) : ?> current<?php endif; ?>">
      <?php $about_page_id = TemplateMap()->get_id_from_template( 'template-about.php' ); ?>
      <a href="<?php echo get_permalink( $about_page_id ); ?>">About</a>
    </li>
  </ul>
</nav>
```

Based on the same principle you can use this utility method to properly orient yourself when outputting conditional classes contingent on the current page being within a 'secton' on your site.
