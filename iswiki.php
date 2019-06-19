<?php

/*
	Copyright (c) 2015-2018, Maximilian Doerr

	This file is part of IABot's Framework.

	IABot is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	IABot is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with IABot.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @file
 * nowikiParser object
 * @author Maximilian Doerr (Cyberpower678)
 * @license https://www.gnu.org/licenses/gpl.txt
 * @copyright Copyright (c) 2015-2018, Maximilian Doerr
 */

/**
 * nowikiParser class
 * Extension of the master parser class specifically for en.wikipedia.org
 * @author Maximilian Doerr (Cyberpower678)
 * @license https://www.gnu.org/licenses/gpl.txt
 * @copyright Copyright (c) 2015-2018, Maximilian Doerr
 * Altered for iswiki by Þjarkur
 */
class nowikiParser extends Parser {

	/**
	 * Get page date formatting standard
	 *
	 * @param bool|string $default Return default format, or return supplied date format of timestamp, provided a page
	 *     tag doesn't override it.
	 *
	 * @access protected
	 * @abstract
	 * @author Maximilian Doerr (Cyberpower678)
	 * @license https://www.gnu.org/licenses/gpl.txt
	 * @copyright Copyright (c) 2015-2018, Maximilian Doerr
	 * @return string Format to be fed in time()
	 */
	protected function retrieveDateFormat( $default = false ) {
		if( !is_bool( $default ) &&
		        preg_match( '/\d\d? (?:January|janúar|February|febrúar|March|mars|April|apríl|May|maí|June|júní|July|júlí|August|ágúst|September|september|October|október|November|nóvember|December|desember) \d{4}/i',
		                    $default
		        )
		) return '%-e %B %Y';
		elseif( !is_bool( $default ) &&
		        preg_match( '/(?:January|janúar|February|febrúar|March|mars|April|apríl|May|maí|June|júní|July|júlí|August|ágúst|September|september|October|október|November|nóvember|December|desember) \d\d?\, \d{4}/i',
		                    $default
		        )
		) return '%B %-e, %Y';
		else return '%Y-%m-%d';
	}

	/**
	 * Generates an appropriate archive template if it can.
	 *
	 * @access protected
	 * @abstract
	 * @author Maximilian Doerr (Cyberpower678)
	 * @license https://www.gnu.org/licenses/gpl.txt
	 * @copyright Copyright (c) 2015-2018, Maximilian Doerr
	 *
	 * @param $link Current link being modified
	 * @param $temp Current temp result from fetchResponse
	 *
	 * @return bool If successful or not
	 */
	protected function generateNewArchiveTemplate( &$link, &$temp ) {
		//We need the archive host, to pick the right template.
		if( !isset( $link['newdata']['archive_host'] ) ) $link['newdata']['archive_host'] =
			$this->getArchiveHost( $temp['archive_url'] );
		//If the archive template is being used improperly, delete the parameters, and start fresh.
		if( $link['has_archive'] === true &&
		    $link['archive_type'] == "invalid"
		) unset( $link['archive_template']['parameters'] );
		switch( $link['newdata']['archive_host'] ) {
			case "wayback":
				$link['newdata']['archive_template']['name'] = "Wayback";
				$link['newdata']['archive_template']['parameters']['url'] = $link['url'];
				if( $temp['archive_time'] != 0 ) $link['newdata']['archive_template']['parameters']['date'] =
					date( 'YmdHis', $temp['archive_time'] );
				else $link['newdata']['archive_template']['parameters']['date'] = "*";
				switch( $this->retrieveDateFormat() ) {
					case 'j F Y':
						$link['newdata']['archive_template']['parameters']['df'] = "yes";
						break;
				}
				break;
			case "webcite":
				$link['newdata']['archive_template']['name'] = "WebCite";
				$link['newdata']['archive_template']['parameters']['url'] = $temp['archive_url'];
				if( $temp['archive_time'] != 0 ) $link['newdata']['archive_template']['parameters']['date'] =
					date( 'YmdHis', $temp['archive_time'] );
				switch( $this->retrieveDateFormat() ) {
					case 'F j Y':
						$link['newdata']['archive_template']['parameters']['dateformat'] = "mdy";
						break;
					case 'j F Y':
						$link['newdata']['archive_template']['parameters']['dateformat'] = "dmy";
						break;
					default:
						$link['newdata']['archive_template']['parameters']['dateformat'] = "iso";
						break;
				}
				break;
			default:
				return false;
		}

		return true;
	}

	/**
	 * Modify link that can't be rescued
	 *
	 * @param array $link Link being analyzed
	 * @param array $modifiedLinks Links modified array
	 *
	 * @access protected
	 * @abstract
	 * @author Maximilian Doerr (Cyberpower678)
	 * @license https://www.gnu.org/licenses/gpl.txt
	 * @copyright Copyright (c) 2015-2018, Maximilian Doerr
	 * @return void
	 */
	protected function noRescueLink( &$link, &$modifiedLinks, $tid, $id ) {
		$modifiedLinks["$tid:$id"]['type'] = "tagged";
		$modifiedLinks["$tid:$id"]['link'] = $link['url'];
		if( $link['link_type'] == "template" && $link['has_archive'] === true ) {
			if( $this->getCiteDefaultKey( "deadurl", $link['link_template']['language'] ) !== false ) {
				$link['newdata']['tag_type'] = "parameter";
				if( $this->getCiteDefaultKey( "deadurlyes", $link['link_template']['language'] ) === false ) {
					$link['newdata']['link_template']['parameters'][$this->getCiteActiveKey( "deadurl", $link['link_template']['language'],
					                                                                         $link['link_template'],
					                                                                         true
					)] = "yes";
				} else {
					$link['newdata']['link_template']['parameters'][$this->getCiteActiveKey( "deadurl",
					                                                                         $link['link_template']['language'],
					                                                                         $link['link_template'],
					                                                                         true
					)] = $this->getCiteDefaultKey( "deadurlyes", $link['link_template']['language'] );
				}
			}
		} else {
			$link['newdata']['tag_type'] = "template";
			$link['newdata']['tag_template']['name'] = "dead link";
			$link['newdata']['tag_template']['parameters']['dags'] = self::strftime( '%B %Y' );
			$link['newdata']['tag_template']['parameters']['bot'] = USERNAME;
		}
	}

	/**
	 * Analyze the remainder string
	 *
	 * @param array $returnArray Array being generated in master function
	 * @param string $remainder Remainder string
	 *
	 * @access protected
	 * @abstract
	 * @author Maximilian Doerr (Cyberpower678)
	 * @license https://www.gnu.org/licenses/gpl.txt
	 * @copyright Copyright (c) 2015-2018, Maximilian Doerr
	 * @return void
	 */
	protected function analyzeRemainder( &$returnArray, &$remainder ) {
		//If there's an archive tag, then...
		if( preg_match( $this->fetchTemplateRegex( $this->commObject->config['archive_tags'] ), $remainder, $params2
		) ) {
			$returnArray['archive_type'] = "template";
			$returnArray['archive_template'] = [];
			$returnArray['archive_template']['parameters'] = $this->getTemplateParameters( $params2[2] );
			$returnArray['archive_template']['name'] = str_replace( "{{", "", $params2[1] );
			$returnArray['archive_template']['string'] = $params2[0];

			//If there already is an archive in this source, it's means there's an archive template attached to a citation template.  That's needless confusion when sourcing.
			if( $returnArray['link_type'] == "template" && $returnArray['has_archive'] === false ) {
				$returnArray['archive_type'] = "invalid";
				$returnArray['tagged_dead'] = true;
				$returnArray['tag_type'] = "implied";
			}

			$returnArray['has_archive'] = true;

			//If there is a wayback tag present, process it
			if( preg_match( $this->fetchTemplateRegex( $this->commObject->config['archive1_tags'] ), $remainder,
			                $params2
			) ) {
				$returnArray['archive_host'] = "wayback";

				//Look for the URL.  If there isn't any found, the template is being used wrong.
				if( isset( $returnArray['archive_template']['parameters']['url'] ) ) {
					$url = htmlspecialchars_decode( $this->filterText( $returnArray['archive_template']['parameters']['url'], true ) );
				} elseif( isset( $returnArray['archive_template']['parameters'][1] ) ) {
					$url = htmlspecialchars_decode( $this->filterText( $returnArray['archive_template']['parameters'][1], true ) );
				} elseif( isset( $returnArray['archive_template']['parameters']['site'] ) ) {
					$url = htmlspecialchars_decode( $this->filterText( $returnArray['archive_template']['parameters']['site'], true ) );
				} else {
					$returnArray['archive_url'] = "x";
					$returnArray['archive_type'] = "invalid";
				}

				//Look for archive timestamp.  If there isn't any, then it's not pointing a snapshot, which makes it harder for the reader and other editors.
				if( isset( $returnArray['archive_template']['parameters']['date'] ) ) {
					$returnArray['archive_time'] =
						self::strtotime( $timestamp = $this->filterText( $returnArray['archive_template']['parameters']['date'], true ) );
					$returnArray['archive_url'] =
						"https://web.archive.org/web/$timestamp/$url";
				} else {
					$returnArray['archive_time'] = "x";
					$returnArray['archive_url'] = "https://web.archive.org/web/*/$url";
					$returnArray['archive_type'] = "invalid";
				}

				//If the original URL isn't present, then we are dealing with a stray archive template.
				if( !isset( $returnArray['url'] ) ) {
					$returnArray['archive_type'] = "invalid";
					$returnArray['url'] = $url;
					$returnArray['link_type'] = "stray";
					$returnArray['is_archive'] = true;
				}

				//Check for a malformation or template misuse.  The URL field needs the original URL, not the archive URL.
				if( $returnArray['archive_url'] == "x" || strpos( $url, "archive.org" ) !== false ) {
					if( preg_match( '/archive\.org\/(web\/)?(\d*?|\*)\/(\S*)\s?/i', $url, $params3 ) ) {
						$returnArray['archive_type'] = "invalid";
						if( $params3[2] != "*" ) $returnArray['archive_time'] = self::strtotime( $params3[2] );
						else $returnArray['archive_time'] = "x";
						$returnArray['archive_url'] = "https://web." . $this->filterText( $params3[0], true );
					} else {
						$returnArray['archive_type'] = "invalid";
					}
				}
			} //If there is a webcite tag present, process it
			elseif( preg_match( $this->fetchTemplateRegex( $this->commObject->config['archive2_tags'] ), $remainder,
			                    $params2
			) ) {
				$returnArray['archive_host'] = "webcite";
				//Look for the URL.  If there isn't any found, the template is being used wrong.
				if( isset( $returnArray['archive_template']['parameters']['url'] ) ) {
					$returnArray['archive_url'] =
						htmlspecialchars_decode( $this->filterText( $returnArray['archive_template']['parameters']['url'], true )  );
				} elseif( isset( $returnArray['archive_template']['parameters'][1] ) ) {
					$returnArray['archive_url'] =
						htmlspecialchars_decode( $this->filterText( $returnArray['archive_template']['parameters'][1], true ) );
				} else {
					$returnArray['archive_url'] = "x";
					$returnArray['archive_type'] = "invalid";
				}

				//Look for the archive timestamp.  Since the Webcite archives use a unique URL for each snapshot, a missing date stamp does not mean invalid usage.
				if( isset( $returnArray['archive_template']['parameters']['dags'] ) ) {
					$returnArray['archive_time'] =
						self::strtotime( $this->filterText( $returnArray['archive_template']['parameters']['dags'], true ) );
				} elseif( isset( $returnArray['archive_template']['parameters']['date'] ) ) {
					$returnArray['archive_time'] =
						self::strtotime( $this->filterText( $returnArray['archive_template']['parameters']['date'], true ) );
				} else {
					$returnArray['archive_time'] = "x";
				}

				//If the original URL isn't present, then we are dealing with a stray archive template.
				if( !isset( $returnArray['url'] ) ) {
					//resolve the archive to the original URL
					API::isArchive( $returnArray['archive_url'], $returnArray );
					$returnArray['archive_type'] = "invalid";
					$returnArray['link_type'] = "stray";
					$returnArray['is_archive'] = true;
				}
			}

			//If we have multiple archives, we can't handle these correctly, so remove any force markers that may force the editing of the citations.
			if( $returnArray['link_type'] == "template" && $returnArray['has_archive'] === true &&
			    $returnArray['archive_type'] == "template"
			) {
				unset( $returnArray['convert_archive_url'] );
				unset( $returnArray['force_when_dead'] );
				unset( $returnArray['force'] );
				unset( $returnArray['force_when_alive'] );
			}
		}

		if( preg_match( $this->fetchTemplateRegex( $this->commObject->config['deadlink_tags'] ), $remainder, $params2
		) ) {
			$returnArray['tagged_dead'] = true;
			$returnArray['tag_type'] = "template";
			if( isset( $params2[2] ) ) $returnArray['tag_template']['parameters'] =
				$this->getTemplateParameters( $params2[2] );
			else $returnArray['tag_template']['parameters'] = [];
			$returnArray['tag_template']['name'] = str_replace( "{{", "", $params2[1] );
			$returnArray['tag_template']['string'] = $params2[0];
		}
	}

	/**
	 * Return a unix timestamp allowing for international support through abstract functions.
	 *
	 * @param $string A timestamp
	 *
	 * @access public
	 * @static
	 * @author Maximilian Doerr (Cyberpower678)
	 * @license https://www.gnu.org/licenses/gpl.txt
	 * @copyright Copyright (c) 2015-2018, Maximilian Doerr
	 * @return int|false A unix timestamp or false on failure.
	 */
	public static function strtotime( $string ) {
		$string = preg_replace( '/janúar/i', "January", $string );
		$string = preg_replace( '/febrúar/i', "February", $string );
		$string = preg_replace( '/mars/i', "March", $string );
		$string = preg_replace( '/apríl/i', "April", $string );
		$string = preg_replace( '/maí/i', "May", $string );
		$string = preg_replace( '/júní/i', "June", $string );
		$string = preg_replace( '/júlí/i', "July", $string );
		$string = preg_replace( '/ágúst/i', "August", $string );
		$string = preg_replace( '/september/i', "September", $string );
		$string = preg_replace( '/október/i', "October", $string );
		$string = preg_replace( '/nóvember/i', "November", $string );
		$string = preg_replace( '/desember/i', "December", $string );

		return strtotime( $string );
	}
}
