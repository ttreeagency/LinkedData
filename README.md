# JSON Linked Data Helpers for Neos CMS

This package extend the Neos Content Respository Node Type configuration to convert
Node or collection of Nodes to JSON-LD.

Installation
------------

    composer require ttree/linkeddata

Features
--------

- [x] Support simple document
- [x] Use EEL queries
- [x] Support relation between document(s)
- [ ] Validation of the generated LinkedData

Add presets to your Node Type configuration
-------------------------------------------

First you need to edit your Node Type configuration (NodeTypes.yaml), the example bellow is for
a course (Workshop) that can contains many sessions (Course Instance in the Schema.org terminology). 
Each Session can have a dedicated Location:

    'Your.Package:Workshop':
      options:
        TtreeLinkedData:Generator:
          default:
            context:
              sessions: "${q(node).children('sessions').children('[instanceof Your.Package:Session]').sort('begin', 'ASC').get()}"
              nextSessions: "${sessions ? q(sessions).filterByDate('end', Date.now()).get() : null}"
              description: "${q(node).children('main').find('[instanceof Neos.NodeTypes:Text]').get(0)}"
            fragment:
              '@context': http://schema.org
              '@type': Course
              name: "${q(node).property('title')}"
              description: "${String.stripTags(String.cropAtSentence(q(description).property('text'), 180, '...'))}"
              hasCourseInstance: "${nextSessions ? LinkedData.List(nextSessions, preset, false) : null}"
    
    'Your.Package:Session':
      options:
        TtreeLinkedData:Generator:
          default:
            context:
              ISO8601: "Y-m-d\TH:i:sO"
              where: "${q(node).property('where')}"
              course: "${q(node).closest('[instanceof Neos.Neos:Document]').get(0)}"
              description: "${q(course).children('main').find('[instanceof Neos.NodeTypes:Text]').get(0)}"
            fragment:
              '@context': http://schema.org
              '@type': CourseInstance
              name: "${q(course).property('title')}"
              description: "${String.stripTags(String.cropAtSentence(q(description).property('text'), 180, '...'))}"
              startDate: "${Date.format(q(node).property('begin'), ISO8601)}"
              endDate: "${Date.format(q(node).property('end'), ISO8601)}"
              location: "${LinkedData.item(where, preset, false)}"
              
    'Your.Package:Location':
      options:
        TtreeLinkedData:Generator:
          default:
            fragment:
              '@context': http://schema.org
              '@type': Place
              name: "${q(node).property('title')}"
              address:
                @type: PostalAddress
                addressLocality: "${q(node).property('addressLocality')}"
                addressRegion: "${q(node).property('addressRegion')}"
                postalCode: "${q(node).property('postalCode')}"
                streetAddress: "${q(node).property('streetAddress')}"
  
You can use multiple presets (```default``` is the preset name). Most ```LinkedData``` EEL helper accept a preset paramater.

Each presets contains two section, the ```context``` configuration and the linked data ```fragment```.

## Understanding ```context```

The context contains a list of key value pairs. All values are available in the EEL context during expression parsing.

## Understanding ```fragment```

The framgment contains the template of the JSON-LD graph. The template can be nested. The value of each keys can be a static
string or an EEL expression (see bellow to the list of EEL helpers available in the package).

## Render the JSON-LD Graph in the HEAD section

To render the JSON-LD graph from all Workshop pages:

    prototype(Your.Package:WorkshopDocument) {
        head.linkedData = Neos.Fusion:Array {
            document = ${LinkedData.render(documentNode, 'default')}
        }
    }

With this snippet, Neos will render automatically the JSON-LD content in hte HEAD section of your document.

## Render the JSON-LD Graph inside your document BODY

You can also render JSON-LD inside the body of your document, in the case, the prototype ```Ttree.LinkedData:Decorator``` can be useful.

Let's say you have a prototype to render your Workshop page content, name ```Your.Package:WorkshopDocument```, 
the following snippet will add the JSON-LD after your title.

	Your.Package:WorkshopDocument.@process.jsonld = Ttree.LinkedData:Decorator

By default this prototype use the default preset and the current document, but you can configure it:

	Your.Package:WorkshopDocument.@process.jsonld = Ttree.LinkedData:Decorator {
		preset = 'myCustomPreset'
		node = ${node}
	}

Render JSON-LD from Settings or custom EEL helpers
--------------------------------------------------

In some case you can render static JSON-LD (like Organization or Website) and need to use custom EEL helper to prepare the data.

    Your:
      Package:
        linkedData:
          website:
            '@context': http://schema.org
            '@type': WebSite
            '@id': '#website'
            url: http://yourdomain.com/
            name: Your website name
            potentialAction:
			  '@type': SearchAction
			  target: http://yourdomain.com/?s={search_term_string}
			  query-input: "required name=search_term_string"
          organization:
            '@context': http://schema.org
            '@type': Organization
            '@id': "#organization"
            url: http://yourdomain.com/
            name: Your organization name

The ```Website``` and ```Organization``` needs to be rendered on the homepage only:

    prototype(Your.Package:HomeDocument) {
        head.linkedData = Neos.Fusion:Array {
            website = ${LinkedData.renderRaw(Configuration.setting('Your.Package.linkedData.website'))}
            organization = ${LinkedData.renderRaw(Configuration.setting('Your.Package.linkedData.organization'))}
        }
    }

Replace the ```Configuration``` EEL helper by your own if you need dynamic data.

Available EEL Helpers
---------------------

## LinkedData.render

    LinkedData.render(NodeInterface $node, $preset = 'default'): string

This helper accept a ```NodeInterface``` instance and preset name. The helper will render the full JSON-LD graph 
and output a valid HTML5 script tag.

## LinkedData.renderRaw

    LinkedData.renderRaw(array $data): string

This helper accept an array. The helper will render the full JSON-LD graph and output a valid HTML5 script tag.

## LinkedData.list

    LinkedData.list(array $collection, $preset = 'default', bool $withContext = true): array

This helper a collection of ```NodeInterface``` instance, a preset name and boolean switch to print of not the ```@context``` key. The helper will return an array of the JSON-LD graph.

You can use this helper in your preset configuration to render a one to one/many relation (see the hasCourseInstance in the default preset for the Your.Package:Workshop node type.)

## LinkedData.item

    LinkedData.item(NodeInterface $node, $preset = 'default', bool $withContext = true): array

This helper a ```NodeInterface``` instance, a preset name and boolean switch to print of not the ```@context``` key. The helper will return an array of the JSON-LD graph.

You can use this helper in your preset configuration to render a one to one relation.


Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to
sponsoring, support request, ... just contact us.

License
-------

Licensed under MIT, see [LICENSE](LICENSE)
