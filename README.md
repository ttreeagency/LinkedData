# JSON Linked Data Helpers for Neos CMS

This package extend the Neos Content Respository Node Type configuration to convert
Node or collection of Nodes to JSON-LD.

**This package is under development and API can change, is you have suggestions/problems, please open an issue**

Installation
------------

    composer require ttree/linkeddata

Usage
-----

First you need to edit your Node Type configuration (NodeTypes.yaml), the example bellow is for
a course (Workshop) that can contains many sessions (Course Instance in the Schema.org terminology). 
Each Session can have a dedicated Location:

    'Your.Package:Workshop':
      options:
        TtreeLinkedData:Generator:
          default:
            context:
              sessions: "${q(node).children('sessions').children('[instanceof Ttree.AtelierAnnedominiqueCh:Session]').sort('begin', 'ASC').get()}"
              nextSessions: "${sessions ? q(sessions).filterByDate('end', Date.now()).get() : null}"
              description: "${q(node).children('main').find('[instanceof Neos.NodeTypes:Text]').get(0)}"
            fragment:
              @context: http://schema.org
              @type: Course
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
              @context: http://schema.org
              @type: CourseInstance
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
              @context: http://schema.org
              @type: Place
              name: "${q(node).property('title')}"
              address:
                @type: PostalAddress
                addressLocality: "${q(node).property('addressLocality')}"
                addressRegion: "${q(node).property('addressRegion')}"
                postalCode: "${q(node).property('postalCode')}"
                streetAddress: "${q(node).property('streetAddress')}"
  
Then you can render the JSON-LD graph from any Workshop page, like this:

    prototype(Neos.Neos:Page) {
        head.linkedData = Neos.Fusion:Array {
            workshop = ${LinkedData.render(documentNode, 'default')}
        }
    }

Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to
sponsoring, support request, ... just contact us.

License
-------

Licensed under MIT, see [LICENSE](LICENSE)
