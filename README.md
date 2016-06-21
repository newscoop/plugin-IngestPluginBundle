IngestPluginBundle
===================

This plugin adds functionality to Newscoop for importing external data sources.

Support Newscoop version 4.4

Installation
-------------
Installation is a quick process:


1. How to install this plugin?
2. That's all!

### Step 1: How to install this plugin?
Run the command:
``` bash
$ php application/console plugins:install "newscoop/ingest-plugin-bundle"
$ php application/console assets:install public/
```
Plugin will be installed to your project's `newscoop/plugins/Newscoop` directory.

### Step 2: That's all!
Go to Newscoop Admin panel and then open `Plugins` tab. The Plugin will show up there. You can now use the plugin.


**Note:**

To update this plugin run the command:
``` bash
$ php application/console plugins:update "newscoop/ingest-plugin-bundle"
$ php application/console assets:install public/
```

To remove this plugin run the command:
``` bash
$ php application/console plugins:remove "newscoop/ingest-plugin-bundle"
```

Documentation
-------------

### Parsers ###
By default there are two parsers included for these external sources.  The RSS
parser works out of the box and supports RSS 1.0, RSS 2.0 and ATOM feeds. The
NewsML parser is based on the SDA implementation of NewsML but needs a little
configuration, since most NewsML feeds are pushed via FTP to the server. The
parser already includes basic functionality to read xml files from a directory.
Extra parser can be installed by creating a file in the Parsers directory of
this plugin. The abstract parser should be extended, since it already contains
all valid methods and returns default valid values. One should just extend the
needed methods and it's done. It's also possible to store additional information
through the getAttribute() methods. See source code for more clarification.

### Feeds ###
Multiple feeds can be added, using the same or different parsers. Per feed it's
possible to set publication and section where the external content should be
published, the parser can also specific a section per entry.
Feeds can be updated manually through the backend interface or through console
commands, which can also be used in a cron.
Autmatic publishing of feeds is also possible.

### Entries ###
All entries will be listed and can manually be published or prepared. By
preparing an entry an article will be created, which could be edited by the
user. Remember though that on updates of the feed the article content could be
overwritten.
Entries will be automatically updated with the correct content, uniqueness is
determined by the newsItemId. Through the getInstruction method in the parser
one can also specify to delete an entry.

License
-------

This bundle is under the GNU General Public License v3. See the complete license in the bundle:

    LICENSE

About
-------
This Bundle is a [Sourcefabric z.ú.](https://github.com/sourcefabric) initiative.
