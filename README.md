# PBS Check DMA

PBS Check DMA is a plugin to to check if a visitor is physically in the DMA of a PBS station


## Contents

PBS Check DMA includes the following files:

* pbs-check-dma.php, which invokes the plugin and provides the basic required functions.
* A subdirectory named `classes` containing the core PHP class files that most functions depend on.
* A subdirectory named `assets` containing JavaScript, CSS, and image files.
* A subdirectory named 'templates' containing some PHP template files

## Installation

1. Copy the `pbs-check-dma` directory into your `wp-content/plugins` directory
2. Navigate to the *Plugins* dashboard page
3. Locate the menu item that reads *PBS Check DMA*
4. Click on *Activate*
5. Navigate to *Settings* and select *PBS Check DMA Settings* 

## Usage

* Add the shortcode 'pbs-check-dma' to any page to invoke the dma checker.  
* TK add arguments for 'matched-div' and 'failed-div' to specify CSS divs that will be displayed based on whether the visitor is in the station's DMA

## Changelog

0.1 2017-07-18 Initial base code


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
