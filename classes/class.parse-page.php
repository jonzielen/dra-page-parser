<?php
namespace jon;
class PageParse {

  public $urlToParse;
  public $baseURL;
  public $productsCount;
  public $itemsToSkip = array();
  protected $wholeStoreString;
  protected $productsArray = array();
  public $items;

  public function __construct($urlToParse, $baseURL) {
    $this->urlToParse = $urlToParse;
    $this->baseURL = $baseURL;
    self::loadFilesToSkip();
    self::loadAllPages($this->urlToParse);
  }

  protected function loadFilesToSkip() {
    if (file_exists('assets/items-to-skip.txt')) {
      $this->itemsToSkip = file('assets/items-to-skip.txt', FILE_IGNORE_NEW_LINES);
    }
  }

  protected function loadAllPages($pageUrl) {
    $i = 0;
    $this->wholeStoreString = '';

    while (preg_match('/No products found/', strip_tags(file_get_contents($pageUrl.'?page='.$i++))) == false) {
      $this->wholeStoreString .= file_get_contents($pageUrl.'?page='.$i);
    }

    self::parseProducts($this->wholeStoreString);
  }

  protected function parseProducts($wholeStoreString) {
    preg_match_all('/<div class="product(.*?)">(.*?)<\/div>(.*?)<\/div>/si', $wholeStoreString, $matches, PREG_PATTERN_ORDER);
    $this->productsCount = count($matches[0]);

    if (count($matches[0]) >= 1) {
      self::parseStringForArray($matches);
      self::addTitleToSkip($matches);
    } else {
      die();
    }
  }

  protected function parseStringForArray($matches) {
    $i = 0;
    foreach ($matches[0] as $match) {
      preg_match('/<h4 class="title">(.*?)<\/h4>/si', $match, $title);

      if (!in_array($title[1], $this->itemsToSkip)) {

        // vendor
        preg_match('/<span class="vendor">(.*?)<\/span>/si', $match, $vendor);
        $this->productsArray[$i]['vendor'] = $vendor[1];

        // title
        preg_match('/<h4 class="title">(.*?)<\/h4>/si', $match, $title);
        $this->productsArray[$i]['title'] = $title[1];

        // price
        preg_match('/<span class="price">(.*?)<\/span>/si', $match, $price);
        $price[1] = str_replace("\n", "", $price[1]);
        $price[1] = str_replace("\r", "", $price[1]);
        $price[1] = trim($price[1]);
        $this->productsArray[$i]['price'] = $price[1];

        // image
        preg_match('/<img src="(.*?)" alt="(.*?)">/si', $match, $img);
        $this->productsArray[$i]['image'] = $img[1];

        // link
        preg_match('/href="(.*?)"/si', $match, $href);
        $this->productsArray[$i]['href'] = $this->baseURL.$href[1];

        $i++;
      }
    }

    unset($this->wholeStoreString);

    if (0 < count($this->productsArray)) {
      self::addItemsToEmail($this->productsArray);
    } else {
      die();
    }
  }

  protected function addItemsToEmail($productsArray) {
    $products = '';
    $product = '';

    for ($i=0; $i < count($this->productsArray); $i++) {
      $product = self::addItemsToTemplate($this->productsArray[$i]);
      $products .= $product;
    }

    $this->items = $products;

    // send email
    self::sendEmail();
  }

  protected function sendEmail() {
    $emailTemplate = file_get_contents('assets/email.html');
    $emailTemplate = str_replace("{email_message}", $this->items, $emailTemplate);

    $email['to'] = 'jonzielen@gmail.com, adrian.m.morse@gmail.com';
    $email['subject'] = 'New Ryan Adams Items';
    $email['headers'] = "From: jon@zielenkievicz.com"."\r\n";
    $email['headers'] .= "Reply-To: jon@zielenkievicz.com"."\r\n";
    $email['headers'] .= "MIME-Version: 1.0\r\n";
    $email['headers'] .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $email['message'] = $emailTemplate;

    $emailList = explode(',', $email['to']);

    foreach ($emailList as $key => $value) {
      mail($value, $email['subject'], $email['message'], $email['headers']);
    }
  }

  protected function addItemsToTemplate($productFromArray) {
    $template = '<a href="{href}" style="text-decoration:none;">'."\n\r";
    $template .= '<h2 style="font-family:Arial, Helvetica, sans-serif;font-size:20px;color:#000001;font-weight:bold;margin-auto:0px;mso-line-height-rule:exactly;line-height:125%;">{title}</h2>'."\n\r";
    $template .= '<h3 style="font-family:Arial, Helvetica, sans-serif;font-size:14px;color:#000001;font-weight:bold;margin-auto:0px;mso-line-height-rule:exactly;line-height:125%;">{price}</h3>'."\n\r";
    $template .= '<table cellspacing="0" cellpadding="0" border="0" align="center"><tr><td><img src="http:{image}" style="display:block;border:none;" alt="{title}" /></td></tr><tr><td height="15" style="height:14px !important;line-height:1px;font-size:0px;"><span style="font-size:0;line-height:0;">&nbsp;</span></td></tr></table>'."\n\r";
    $template .= '</a>'."\n\r";

    foreach ($productFromArray as $key => $value) {
      $template = str_replace("{".$key."}", $value, $template);
    }

    return $template;
  }

  protected function addTitleToSkip($matches) {
    $i = 0;
    foreach ($matches[0] as $skipTitles) {
      preg_match('/<h4 class="title">(.*?)<\/h4>/si', $skipTitles, $skipTitle);

      if (!in_array($skipTitle[1], $this->itemsToSkip)) {

        // title
        preg_match('/<h4 class="title">(.*?)<\/h4>/si', $skipTitles, $skipTitle);
        self::saveSkipedItems($skipTitle[1]);

        $i++;
      }
    }
  }

  protected function saveSkipedItems($skipTitle) {
    if (file_exists('assets/items-to-skip.txt')) {
      $skipFile = fopen("assets/items-to-skip.txt","a+");
      fwrite($skipFile, $skipTitle."\n");
      fclose($skipFile);
    } else {
      die();
    }
  }

  public function render() {
    echo  $this->items;
  }
}
