# PBS Check DMA

PBS Check DMA is a WordPress plugin that geo-filters video content to visitors who are physically located within the DMA (Designated Marketing Area) of a local PBS station. 


## Contents

PBS Check DMA includes the following files:

* pbs-check-dma.php, which invokes the plugin and provides the basic required functions.
* A subdirectory named `classes` containing the core PHP class files that most functions depend on.
* A subdirectory named `assets` containing JavaScript, CSS, and image files.
* A subdirectory named `templates` containing some PHP template files

## Installation

1. Copy the `pbs-check-dma` directory into your `wp-content/plugins` directory
2. Navigate to the *Plugins* dashboard page
3. Locate the menu item that reads *PBS Check DMA*
4. Click on *Activate*
5. Navigate to *Settings* and select *PBS Check DMA Settings* 
6. Fill in the list of counties, by state
7. To use the more accurate device-based geolocation, sign up with a reverse geolocation provider, and select it on the settings page, including filling in any necessary access info
8. See below for theme integration

## Usage
In order to do any DMA restriction, you must fill out the state/county fields on the settings page with the complete list of counties in your DMA/broadcast area so that the site has something to compare visitor location to.

By default the plugin only checks against the PBS Localization API, which uses IP-based geolocation and is free, but can be wrong up to 10% of the time -- 1 in 10 viewers may be incorrectly blocked. This is particularly an issue for mobile users; the reported locations of their IP addresses can be off by hundreds of miles.

Selecting a Reverse GeoCoding Provider allows the use of the much more accurate 'device location API' that takes advantage of things like GPS etc. This is much better for mobile devices particularly, but all devices with a web browser will have improved results. The device returns a latitude/longitude, which then is sent to a reverse geocoding service to be converted into a info like 'county' and 'zip'. That's not exactly free: all of the providers require signup; most have a 'free' tier (here.com is 250k requests/month, googlemaps is 40k requests/month).

Custom HLS streams can be supported by entering a URL for the JW Player libraries.  See jwplayer.com for details.

## Custom HLS and Shortcode

The plugin creates a meta box on "page" type posts, where URLs for a stream and a mezzanine (16x9) image can be entered.  Entering those fields enables a shortcode that can be placed in any post or page to render either the video player specified for that stream or, if the visitor is outside of geofenced region, language explaining that why the visitor is blocked.

## Theme Integration for PBS-hosted videos

After activating the plugin, wherever you want to render a PBS-hosted but DMA-restricted video create an instance of the PBS_Check_DMA class -- eg

```php
$checkdma = new PBS_Check_DMA();
$player = $checkdma->build_dma_restricted_player(sometpmediaid, url_for_a_mezz_image);
echo $player;
```

("sometpmediaid" would be something like '92424242410', you'd get that from the PBS Media Manager; and "url_for_a_mezz_image" would look something like 'https://image.pbs.org/video-assets/AIdcUYK-asset-mezzanine-16x9-ccRViYN.jpg')

That will write out a DIV with the class 'dmarestrictedplayer' that will enclose a thumbnail image sized to 1200x675. That will also automatically enqueue appropriate javascript and CSS on the page to act on any div with that class. The javascript will then act on that class to look up the location of your visitor, compare it to the list of allowed counties, and if a match is found write out a PBS 'Partner Player' using the supplied TP Media Object ID.

For more manual control, you could also enqueue those files with

```php
$checkdma = new PBS_Check_DMA();
$checkdma->enqueue_scripts();
```

and manually write out that DIV with a 'data-media' property with the value being the desired TP Media Object ID, enclosing the appropriate thumb image of your choice. 

## Changelog

* 0.6 2020-03-20 Shortcode that JW Player integration for custom streams 
* 0.4 2018-11-29 Complete revamp 
* 0.1 2017-07-18 Initial base code


## License

The PBS Check DMA plugin is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

> This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

> You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
