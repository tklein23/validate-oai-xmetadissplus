#!/usr/bin/php
<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License 
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51 
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Application
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2012-2013 Thoralf Klein
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

if (count($argv) != 2) {
   echo "usage: ./validate-oai-xml.php [xml file]\n";
   exit(1);
}

$file = $argv[1];

libxml_use_internal_errors(true);
libxml_clear_errors();

function check_libxml_errors($xml) {
   $errors = libxml_get_errors();
   if (empty($errors)) {
      return true;
   }

   $noXmlErrors = true;

#   $error = $errors[0];
#   if ($error->level < 3) {
   foreach ($errors AS $error) {
#      if ($error->level < 3) {
      if ($error->level < 2) {
         continue;
      }

      $lines = explode("\n", $xml);
      $line = $lines[($error->line)-1];
      echo "ERROR(".$error->level."): \n";
      echo "\t" . trim($error->message) . ' at line ' . $error->line . ":\n";
      echo "\t" . trim($line) . "\n";

      $noXmlErrors = false;
   }

   libxml_clear_errors();
   return $noXmlErrors;
}

$xdoc = new DomDocument;
$xdoc->Load($file);

$xpath = new DOMXPath($xdoc);
$xpath->registerNamespace('oai',       "http://www.openarchives.org/OAI/2.0/");
$xpath->registerNamespace('xMetaDiss', "http://www.d-nb.de/standards/xmetadissplus/");

$nodes = $xpath->query('/*[name()="OAI-PMH"]/*[name()="ListRecords"]/*[name()="record"]');
print "found " . $nodes->length . " nodes\n";

$deletedDocumentIds = array();
$validDocumentIds = array();
$invalidDocumentIds = array();

foreach ($nodes AS $child) {
   $identifier   = $xpath->query('./*[name()="header"]/*[name()="identifier"]', $child)->item(0)->nodeValue;

   $deletedHeader = $xpath->query('./*[name()="header" and @status="deleted"]', $child);
   if ($deletedHeader->length > 0) {
      print "skipping deleted document $identifier\n";
      $deletedDocumentIds[] = $identifier;
      continue;
   }

   $metadataNode = $xpath->query('./*[name()="metadata"]/xMetaDiss:xMetaDiss', $child)->item(0);
   if (false === $metadataNode->hasAttribute('xmlns:xsi')) {
      $attr = $xdoc->createAttribute('xmlns:xsi');
      $text = $xdoc->createTextNode('http://www.w3.org/2001/XMLSchema-instance');
      $attr->appendChild($text);
      $metadataNode->appendChild($attr);
   }

   $metadataXml  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
   $metadataXml .= $metadataNode->ownerDocument->saveXML($metadataNode);
#  $metadataXml = str_replace('<xMetaDiss:xMetaDiss ', '<xMetaDiss:xMetaDiss xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ', $metadataXml);

   echo "loading document $identifier metadata\n";
   $metadataDocument = new DOMDocument();
   $metadataDocument->loadXML($metadataXml);

   $xmlValid = check_libxml_errors($metadataXml);

   echo "validating document $identifier metadata\n";
   $valid = $metadataDocument->schemaValidate('cache/xmetadissplus.xsd');
   if ($valid) {
      print "valid document $identifier\n";
      $validDocumentIds[] = $identifier;
   }
   else {
      $xmlValid = check_libxml_errors($metadataXml);

      print "INVALID document $identifier\n";
      $invalidDocumentIds[] = $identifier;
   }
   echo "\n\n";
}

echo "invalid:\n";
var_dump($invalidDocumentIds);
echo "\n";

echo "deleted:\n";
var_dump($deletedDocumentIds);
echo "\n";

echo "valid:\n";
var_dump($validDocumentIds);
echo "\n";
