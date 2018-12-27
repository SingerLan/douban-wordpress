<?php  

class DoubanAPI
{
    /**
     * �ӱ��ض�ȡ������Ϣ�����������򴴽�������������¡������ظ�ʽ�� JSON
     * 
     * @access  public 
     * @param   string    $UserID             ����ID
     * @param   int       $PageSize           ��ҳ��С
     * @param   int       $From               ��ʼλ��
     * @param   int       $ValidTimeSpan      ��Чʱ�䣬Unix ʱ�����s
     * @return  json      ���ظ�ʽ��Ӱ��
     */  
    public static function updateMovieCacheAndReturn($UserID,$PageSize,$From,$ValidTimeSpan){
        if(!$UserID) return json_encode(array());
        $expired = self::__isCacheExpired(__DIR__.'/cache/movie.json',$ValidTimeSpan);
        if($expired!=0){
            $data=self::__getMovieRawData($UserID);
            $file=fopen(__DIR__.'/cache/movie.json',"w");
            fwrite($file,json_encode(array('time'=>time(),'data'=>$data)));
            fclose($file);
            return self::updateMovieCacheAndReturn($UserID,$PageSize,$From,$ValidTimeSpan);
        }else{
            $data=json_decode(file_get_contents(__DIR__.'/cache/movie.json'))->data;
            $total=count($data);
            if($From<0 || $From>$total-1) echo json_encode(array());
            else{
                $end=min($From+$PageSize,$total);
                $out=array();
                for ($index=$From; $index<$end; $index++) {
                    array_push($out,$data[$index]);
                }
                return json_encode($out);
            }
        }
    }

    /**
     * ��黺���Ƿ����
     * 
     * @access  private
     * @param   string    $FilePath           ����·��
     * @param   int       $ValidTimeSpan      ��Чʱ�䣬Unix ʱ�����s
     * @return  int       0: δ����; 1:�ѹ���; -1���޻���򻺴���Ч
     */
    private static function __isCacheExpired($FilePath,$ValidTimeSpan){
        $file=fopen($FilePath,"r");
        if(!$file) return -1;
        $content=json_decode(fread($file,filesize($FilePath)));
        fclose($file);
        if(!$content->time || $content->time<1) return -1;
        if(time()-$content->time > $ValidTimeSpan) return 1;
        return 0; 
    }

    /**
     * �Ӷ�����ҳ����Ӱ������
     * 
     * @access  private
     * @param   string    $UserID     ����ID
     * @return  array     ���ظ�ʽ�� array
     */
    private static function __getMovieRawData($UserID){
        $api='https://movie.douban.com/people/'.$UserID.'/collect';
        $data=array();
        while($api!=null){
            $raw=file_get_contents($api);
            if($raw==null || $raw=="") break;
            $doc = new ParserDom($raw); 
            $itemArray = $doc->find("div.item");
            foreach ($itemArray as $v) {
                $t = $v->find("li.title", 0);
                $movie_name = str_replace(strstr(str_replace(array(" ", "��", "\t", "\n", "\r"),
                                          array("", "", "", "", ""),$t->getPlainText()),"/"),"",str_replace(array(" ", "��", "\t", "\n", "\r"),
                                          array("", "", "", "", ""),$t->getPlainText()));
                $movie_img  = $v->find("div.pic a img", 0)->getAttr("src");
                $movie_url  = $t->find("a", 0)->getAttr("href");
                $data[] = array("name" => $movie_name, "img" => $movie_img, "url" => $movie_url);
            }
            $url = $doc->find("span.next a", 0);
            if ($url) {
                $api = "https://movie.douban.com" .$url->getAttr("href");
            }else{
                $api = null;
            }
        }
        return $data;
    }

}

class ParserDom {
    /**
     * @var \DOMNode
     */
    public $node;
    /**
     * @var array
     */
    private $_lFind = [];
    /**
     * @param \DOMNode|string $node
     * @throws \Exception
     */
    public function __construct($node = NULL) {
        if ($node !== NULL) {
            if ($node instanceof \DOMNode) {
                $this->node = $node;
            } else {
                $dom = new \DOMDocument();
                $dom->preserveWhiteSpace = FALSE;
                $dom->strictErrorChecking = FALSE;
                if (@$dom->loadHTML($node)) {
                    $this->node = $dom;
                } else {
                    throw new \Exception('load html error');
                }
            }
        }
    }
    /**
     * ��ʼ����ʱ����Բ��ô���html��������Զ��ʹ��
     * @param null $node
     * @throws \Exception
     */
    public function load($node = NULL) {
        if ($node instanceof \DOMNode) {
            $this->node = $node;
        } else {
            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = FALSE;
            $dom->strictErrorChecking = FALSE;
            if (@$dom->loadHTML($node)) {
                $this->node = $dom;
            } else {
                throw new \Exception('load html error');
            }
        }
    }
    /**
     * @codeCoverageIgnore
     * @param string $name
     * @return mixed
     */
    function __get($name) {
        switch ($name) {
            case 'outertext':
                return $this->outerHtml();
            case 'innertext':
                return $this->innerHtml();
            case 'plaintext':
                return $this->getPlainText();
            case 'href':
                return $this->getAttr("href");
            case 'src':
                return $this->getAttr("src");
            default:
                return NULL;
        }
    }
    /**
     * ������Ȳ�ѯ
     *
     * @param string $selector
     * @param number $idx �ҵڼ���,��0��ʼ���㣬null ��ʾ������, ������ʾ�����ڼ���
     * @return self|self[]
     */
    public function find($selector, $idx = NULL) {
        if (empty($this->node->childNodes)) {
            return FALSE;
        }
        $selectors = $this->parse_selector($selector);
        if (($count = count($selectors)) === 0) {
            return FALSE;
        }
        for ($c = 0; $c < $count; $c++) {
            if (($level = count($selectors [$c])) === 0) {
                return FALSE;
            }
            $this->search($this->node, $idx, $selectors [$c], $level);
        }
        $found = $this->_lFind;
        $this->_lFind = [];
        if ($idx !== NULL) {
            if ($idx < 0) {
                $idx = count($found) + $idx;
            }
            if (isset($found[$idx])) {
                return $found[$idx];
            } else {
                return FALSE;
            }
        }
        return $found;
    }
    /**
     * �����ı���Ϣ
     *
     * @return string
     */
    public function getPlainText() {
        return $this->text($this->node);
    }
    /**
     * ��ȡinnerHtml
     * @return string
     */
    public function innerHtml() {
        $innerHTML = "";
        $children = $this->node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $this->node->ownerDocument->saveHTML($child) ?: '';
        }
        return $innerHTML;
    }
    /**
     * ��ȡouterHtml
     * @return string|bool
     */
    public function outerHtml() {
        $doc = new \DOMDocument();
        $doc->appendChild($doc->importNode($this->node, TRUE));
        return $doc->saveHTML($doc);
    }
    /**
     * ��ȡhtml��Ԫ��ֵ
     *
     * @param string $name
     * @return string|null
     */
    public function getAttr($name) {
        $oAttr = $this->node->attributes->getNamedItem($name);
        if (isset($oAttr)) {
            return $oAttr->nodeValue;
        }
        return NULL;
    }
    /**
     * ƥ��
     *
     * @param string $exp
     * @param string $pattern
     * @param string $value
     * @return boolean|number
     */
    private function match($exp, $pattern, $value) {
        $pattern = strtolower($pattern);
        $value = strtolower($value);
        switch ($exp) {
            case '=' :
                return ($value === $pattern);
            case '!=' :
                return ($value !== $pattern);
            case '^=' :
                return preg_match("/^" . preg_quote($pattern, '/') . "/", $value);
            case '$=' :
                return preg_match("/" . preg_quote($pattern, '/') . "$/", $value);
            case '*=' :
                if ($pattern [0] == '/') {
                    return preg_match($pattern, $value);
                }
                return preg_match("/" . $pattern . "/i", $value);
        }
        return FALSE;
    }
    /**
     * ������ѯ���
     *
     * @param string $selector_string
     * @return array
     */
    private function parse_selector($selector_string) {
        $pattern = '/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)["\']?(.*?)["\']?)?\])?([\/, ]+)/is';
        preg_match_all($pattern, trim($selector_string) . ' ', $matches, PREG_SET_ORDER);
        $selectors = [];
        $result = [];
        foreach ($matches as $m) {
            $m [0] = trim($m [0]);
            if ($m [0] === '' || $m [0] === '/' || $m [0] === '//')
                continue;
            if ($m [1] === 'tbody')
                continue;
            list ($tag, $key, $val, $exp, $no_key) = [$m [1], NULL, NULL, '=', FALSE];
            if (!empty ($m [2])) {
                $key = 'id';
                $val = $m [2];
            }
            if (!empty ($m [3])) {
                $key = 'class';
                $val = $m [3];
            }
            if (!empty ($m [4])) {
                $key = $m [4];
            }
            if (!empty ($m [5])) {
                $exp = $m [5];
            }
            if (!empty ($m [6])) {
                $val = $m [6];
            }
            // convert to lowercase
            $tag = strtolower($tag);
            $key = strtolower($key);
            // elements that do NOT have the specified attribute
            if (isset ($key [0]) && $key [0] === '!') {
                $key = substr($key, 1);
                $no_key = TRUE;
            }
            $result [] = [$tag, $key, $val, $exp, $no_key];
            if (trim($m [7]) === ',') {
                $selectors [] = $result;
                $result = [];
            }
        }
        if (count($result) > 0) {
            $selectors [] = $result;
        }
        return $selectors;
    }
    /**
     * ��Ȳ�ѯ
     *
     * @param \DOMNode $search
     * @param          $idx
     * @param          $selectors
     * @param          $level
     * @param int $search_level
     * @return bool
     */
    private function search(&$search, $idx, $selectors, $level, $search_level = 0) {
        if ($search_level >= $level) {
            $rs = $this->seek($search, $selectors, $level - 1);
            if ($rs !== FALSE && $idx !== NULL) {
                if ($idx == count($this->_lFind)) {
                    $this->_lFind[] = new self($rs);
                    return TRUE;
                } else {
                    $this->_lFind[] = new self($rs);
                }
            } elseif ($rs !== FALSE) {
                $this->_lFind[] = new self($rs);
            }
        }
        if (!empty($search->childNodes)) {
            foreach ($search->childNodes as $val) {
                if ($this->search($val, $idx, $selectors, $level, $search_level + 1)) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
    /**
     * ��ȡtidy_node�ı�
     *
     * @param \DOMNode $node
     * @return string
     */
    private function text(&$node) {
        return $node->textContent;
    }
    /**
     * ƥ��ڵ�,���ڲ�ȡ�ĵ�����ң�����ʱ�临�Ӷ�Ϊn+m*l nΪ�ܽڵ�����mΪƥ�����һ������ĸ�����lΪ��������,
     * @codeCoverageIgnore
     * @param \DOMNode $search
     * @param array $selectors
     * @param int $current
     * @return boolean|\DOMNode
     */
    private function seek($search, $selectors, $current) {
        if (!($search instanceof \DOMElement)) {
            return FALSE;
        }
        list ($tag, $key, $val, $exp, $no_key) = $selectors [$current];
        $pass = TRUE;
        if ($tag === '*' && !$key) {
            exit('tagΪ*ʱ��key����Ϊ��');
        }
        if ($tag && $tag != $search->tagName && $tag !== '*') {
            $pass = FALSE;
        }
        if ($pass && $key) {
            if ($no_key) {
                if ($search->hasAttribute($key)) {
                    $pass = FALSE;
                }
            } else {
                if ($key != "plaintext" && !$search->hasAttribute($key)) {
                    $pass = FALSE;
                }
            }
        }
        if ($pass && $key && $val && $val !== '*') {
            if ($key == "plaintext") {
                $nodeKeyValue = $this->text($search);
            } else {
                $nodeKeyValue = $search->getAttribute($key);
            }
            $check = $this->match($exp, $val, $nodeKeyValue);
            if (!$check && strcasecmp($key, 'class') === 0) {
                foreach (explode(' ', $search->getAttribute($key)) as $k) {
                    if (!empty ($k)) {
                        $check = $this->match($exp, $val, $k);
                        if ($check) {
                            break;
                        }
                    }
                }
            }
            if (!$check) {
                $pass = FALSE;
            }
        }
        if ($pass) {
            $current--;
            if ($current < 0) {
                return $search;
            } elseif ($this->seek($this->getParent($search), $selectors, $current)) {
                return $search;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }
    /**
     * ��ȡ���׽ڵ�
     *
     * @param \DOMNode $node
     * @return \DOMNode
     */
    private function getParent($node) {
        return $node->parentNode;
    }
}

$UserID="181244075";
$PageSize=20;
$ValidTimeSpan=60*60*24;
$From=$_GET['from'];
if($_GET['type']=='movie'){
header("Content-type: application/json");
echo DoubanAPI::updateMovieCacheAndReturn($UserID,$PageSize,$From,$ValidTimeSpan);
}else{
echo json_encode(array());
}
?>