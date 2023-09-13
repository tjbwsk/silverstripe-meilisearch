# SilverStripe meilisearch module

## Intro

This module adds support for connecting with meilisearch as (multi-lingual) full-text search engine.

**[mailisearch](https://www.meilisearch.com/)**

> An open-source, lightning-fast, and hyper-relevant search engine that fits effortlessly into your workflow.

## Requirements

* SilverStripe 4.x and 5.x
* PHP 8.1
* meilisearch 1.3 - [self hosted](https://www.meilisearch.com/docs/learn/getting_started/installation)\
_(due to missing supoprt for authorization keys.)_

## Supports

* [silverstripe-fluent](https://github.com/tractorcow-farm/silverstripe-fluent)

## Installation

`composer require bimthebam/silverstripe-meilisearch ^1.0`

## Configuration

This module requires a single environment variable to be defined: `MEILISEARCH_HOST_AND_PORT`

e.g. `MEILISEARCH_HOST_AND_PORT=http://your-meilisearch-host:7700`

## Usage

### Initialization

Run the buit-in task [RebuildAllIndexesTask](src/Dev/Task/RebuildAllIndexesTask.php), which will create all the needed indexes within your meilisearch instance and fills them up with contents.

Although not neccessary, it is suggested to run the task from CLI.

e.g. `sake dev/tasks/meilisearch-rebuild-all-indexes`

### Search

This module comes with a pre-defined index for SiteTree. So searching in page contents should mostly work out of the box.

To start, simply add a new page of type [SearchPage](src/Model/CMS/SearchPage.php) to your site tree.

## Custom indexes

_Documentation incomplete_

## ToDo

- [ ] Add support for authentication keys
- [ ] Complete documentation for custom indexes
