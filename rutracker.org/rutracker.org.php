<?php
class rutracker implements ISite, ISearch, IVerify {
    const SITE = "https://rutracker.org";
    private $url;

    /*
     * rutracker()
     * @param {string} $url
     * @param {string} $username
     * @param {string} $password
     * @param {string} $meta
     */
    public function __construct($url = null, $username = null, $password = null, $meta = null) {
      $this->url = $url;
    }
    
    /*
    * Verify()
    * @return {boolean}
    */
    public function Verify() {
      //TBD
    }
    
    /*
     * Search()
     * @param {string} $keyword
     * @param {integer} $limit
     * @param {string} $category
     * @return {array} SearchLink array
     */
    public function Search($keyword, $limit, $category) {
        $page = 1;
        $keyword = urlencode($keyword);
        
        $ajax = new Ajax();
        $found = array();
        $success = function ($_, $_, $_, $body, $_) use(&$page, &$found, &$limit) {
            preg_match_all(
                "`".
                    "<table.*".
                    "<a.*>(?P<name>.*)</a>.*".
                    "<td .*：<strong>(?P<time>.*)</strong></td>.*".
                    "<td .*：<strong>(?P<size>.*) (?P<unit>.*)</strong></td>.*".
                    "<td .*：<strong>(?P<seeds>.*)</strong></td>.*".
                    "<a href=\"(?P<link>magnet:.*)\".*</a>.*".
                   "</table>".
                "`siU",
                $body,
                $result
            );

            if (!$result || ($len = count($result["name"])) == 0 ) {
                $page = false;
                return;
            }
            
            for ($i = 0 ; $i < $len ; ++$i) {
                $tlink = new SearchLink;
                
                $tlink->src           = "rutracker.org";
                $tlink->link          = $result["link"][$i];
                $tlink->name          = strip_tags($result["name"][$i]);
                $tlink->size          = ($result["size"][$i] + 0) * self::UnitSize($result["unit"][$i]);
                //$tlink->size          = $result["size"][$i];
                $tlink->seeds         = $result["seeds"][$i] + 0;
                // $tlink->peers         = $result["leechers"][$i] + 0;
                // $tlink->time          = $date;
                //$tlink->time          = $result["time"][$i];
                $tlink->time            = DateTime::createFromFormat("Y-m-d", $result["time"][$i]);
                // $tlink->category      = $result["category"][$i];
                $tlink->enclosure_url = $tlink->link;
                
                $found []= $tlink;
                
                if (count($found) >= $limit) {
                    $page = false;
                    return;
                }
            }
            
            ++$page;
        };
        
        while ($page !== false && count($found) < $limit) {
            if (!$ajax->request(Array("url" => rutracker::SITE."/list/$keyword/$page"), $success)) {
                break;
            }
        }
        
        return $found;
    }
    
    /*
     * UnitSize()
     * @param {string} $unit
     * @return {number} sizeof byte
     */
    static function UnitSize($unit) {
        switch (strtoupper($unit)) {
        case "KB": return 1000;
        case "MB": return 1000000;
        case "GB": return 1000000000;
        case "TB": return 1000000000000;
        default: return 1;
        }
    }
}
?>
