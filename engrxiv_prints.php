<?php
// Secrets
$token = join(',', getopt("p:"));
$provider = 'engrxiv';

// Let's gather the list of preprints stored in the engrXiv repository.
require __DIR__ . '/vendor/autoload.php';
use \Curl\Curl;
$curl = new Curl();
$curl->setHeader('Authorization', 'Bearer ' . $token);
$curl->get('https://api.osf.io/v2/preprints/?embed=primary_file&filter[provider]=' . $provider . '&filter[reviews_state][ne]=initial');
if ($curl->error) {
    echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
} else {
  $headers = array('GUID', 'Title', 'Abstract', 'status', 'file url', 'Download count', 'URL', 'Preprint DOI', 'Publisher DOI', 'Date created', 'Date modified');
  // Save the data to a CSV.
  $csv = fopen('engrxiv-papers.csv', 'a+');
  fputcsv($csv, $headers);
  fclose($csv);
  echo 'Success!';
  // Parse a page of results, maximum 10.
  $data = $curl->response->data;
  foreach ($data as &$preprint) {
    pageparse($preprint, $token);
  }
  // We might have another page of results to parse.
  $nextpage = $curl->response->links->next;
  while ($nextpage) {
    nextpage($nextpage, $token);
  }
}

// Function to parse through a page worth of preprint results.
function pageparse($preprint) {
  // Parse the data
  $engrxid = $preprint->id;
  $links = $preprint->links;
  // Grab the URL of the preprint node.
  $url = $links->html;
  // Is the paper published or rejected?
  $attributes = $preprint->attributes;
  $status = $attributes->is_published;
  // Grab the preprint DOI.
  if ($status) {
     $preprintdoilink = $links->preprint_doi;
     $prefix = 'https://doi.org/';
     if (substr($preprintdoilink, 0, strlen($prefix)) == $prefix) {
       $preprintdoi = substr($preprintdoilink, strlen($prefix));
	 }
	 // Grab the publisher DOI.
     $publisherdoi = $attributes->doi;
     // Grab created & modified date, title, abstract, and status.
     $created = $attributes->date_published;
     $modified = $attributes->date_modified;
     $title = $attributes->title;
     $abstract = $attributes->description;
     // Get primary file associated with the preprint
     $file = $preprint->relationships->primary_file->links->related->href;
    // Grab the download counts associated with the embed
     $count = $preprint->embeds->primary_file->data->attributes->extra->downloads;
     // Assemble the data.
     $fields = array($engrxid, $title, $abstract, $status, $file, $count, $url, $preprintdoi, $publisherdoi, $created, $modified);
     // Save the data to a CSV.
     $csv = fopen('engrxiv-papers.csv', 'a+');
     fputcsv($csv, $fields);
     fclose($csv);
  } else {
    // The paper has been rejected, so it won't have a DOI
    $preprintdoi = 'N/A';
  }
}

// Function to grab the next page of results.
function nextpage(&$nextpage, $token) {
  $curl = new Curl();
  $curl->setHeader('Authorization', 'Bearer ' . $token);
  $curl->get($nextpage);
  if ($curl->error) {
      echo 'NextPageError: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
  } else {
    echo 'Success!';
    // Parse a page of results, maximum 10.
    $data = $curl->response->data;
    foreach ($data as &$preprint) {
      pageparse($preprint, $token);
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
