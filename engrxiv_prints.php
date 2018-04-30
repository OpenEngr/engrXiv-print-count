<?php
// Let's gather the list of preprints stored in the engrXiv repository.
require __DIR__ . '/vendor/autoload.php';
use \Curl\Curl;
$curl = new Curl();
$curl->setHeader('Authorization', 'Bearer AUTHTOKEN');
$curl->get('https://api.osf.io/v2/preprints/?filter[provider]=engrxiv&filter[reviews_state][ne]=initial');
if ($curl->error) {
    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
} else {
  // Give the CSV headers.
  $headers = array('GUID', 'Title', 'Abstract', 'URL', 'DOI', 'Publisher DOI', 'Is published', 'Date created', 'Date modified');
  // Save the data to a CSV.
  $csv = fopen('engrxiv-papers.csv', 'a+');
  fputcsv($csv, $headers);
  fclose($csv);
  echo 'Success!';
  // Parse a page of results, maximum 10.
  $data = $curl->response->data;
  foreach ($data as &$preprint) {
    pageparse($preprint);
  }
  // We might have another page of results to parse.
  $nextpage = $curl->response->links->next;
  while ($nextpage) {
    nextpage($nextpage);
  }
}

// Function to parse through a page worth of preprint results.
function pageparse($preprint) {
  // Parse the data
  $engrid = $preprint->relationships->node->data->id;
  $links = $preprint->links;
  // Grab the URL of the preprint node.
  $url = $links->html;
  // Grab the preprint DOI.
  $preprintdoilink = $links->preprint_doi;
  $prefix = 'https://dx.doi.org/';
  if (substr($preprintdoilink, 0, strlen($prefix)) == $prefix) {
    $preprintdoi = substr($preprintdoilink, strlen($prefix));
  }
  // Grab the publisher DOI.
  $attributes = $preprint->attributes;
  $publisherdoi = $attributes->doi;
  // Grab created & modified date, title, abstract, and status.
  $created = $attributes->date_published;
  $modified = $attributes->date_modified;
  $title = $attributes->title;
  $abstract = $attributes->description;
  $status = $attributes->is_published;
  if ($status == '1') {
    $status = 'TRUE';
	// Assemble the data.
    $fields = array($engrid, $title, $abstract, $url, $preprintdoi, $publisherdoi, $status, $created, $modified);
    // Save the data to a CSV.
    $csv = fopen('engrxiv-papers.csv', 'a+');
    fputcsv($csv, $fields);
    fclose($csv);
  }
  else {
    $status = 'FALSE';
  }
}

// Function to grab the next page of results.
function nextpage(&$nextpage) {
  $curl = new Curl();
  $curl->setHeader('Authorization', 'Bearer AUTHTOKEN');
  $curl->get($nextpage);
  if ($curl->error) {
      echo 'NextPageError: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
  } else {
    echo 'Success!';
    // Parse a page of results, maximum 10.
    $data = $curl->response->data;
    foreach ($data as &$preprint) {
      pageparse($preprint);
    }
    // We might have yet another page of results to parse.
    $nextpage = $curl->response->links->next;
    if ($nextpage) {
      return $nextpage;
    }
    else {
      echo 'No more pages left to parse.';
      return FALSE;
    }
  }
}

?>
