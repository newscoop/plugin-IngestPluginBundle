IngestPluginBundle
===================

Newscoop IngestPluginBundle

This plugin adds functionality to Newscoop for importing external data sources.

Support Newscoop version 4.4

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

## Installation/Updating/Removing

### Commands
#### Installation

```
    php application/console plugins:install "m038/ingest-plugin-bundle" --env=prod
```

#### Update

```
    php application/console plugins:update "m038/ingest-plugin-bundle" --env=prod
```

#### Removal

```
    php application/console plugins:remove "m038/ingest-plugin-bundle" --env=prod
```
