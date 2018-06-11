Contao Addons
=============

I use the [Contao CMS](https://contao.org/) for some websites I maintain. This extension 
adds some extra functionality or customizations of mine.

The individual pieces are:
* Daily mail of Contao log messages. This is especially helpful, when I am the formal
  administrator of a site, but other people can edit some of the content. I just like to
  have an overview of what’s going on.
* Download tables as CSV. An operation button is added to tables so they can be exported to 
  CSV files. It’s preconfigured for the members table, but it can be used to export columns 
  from any DCA-backed database table in Contao.
* Enable newletter recipients with multiple mail addresses. Individual Contao members can be 
  subscribed to newsletters. However, only a single mail address is supported per subscribed 
  member. This modification extends newsletters to accept recipients with multiple, 
  comma-separated addresses.

To use this extension, place a copy of it in Contao’s `system/modules/` directory as a 
subdirectory called `addons`. Since this extension targets my use cases, I did not add 
configurability. You may have to change the code. Also, be aware that most output will be in 
German. Please share if you happen to implement proper localization.

This work is licensed under the [WTFPL](http://www.wtfpl.net/), so you can do anything you 
want with it.
