<?php
namespace SAMLBundle\Utils;

class XML
{
    public static function formatXMLString(string $xml, string $indentBase = '')
    {
        try {
            $doc = \SAML2\DOMDocumentFactory::fromString($xml);
        } catch (\Exception $e) {
            throw new \DOMException('Error parsing XML string.');
        }

        $root = $doc->firstChild;
        self::formatDOMElement($root, $indentBase);

        return $doc->saveXML($root);
    }
    
    public static function formatDOMElement(\DOMNode $root, string $indentBase = '')
    {
        // check what this element contains
        $fullText = ''; // all text in this element
        $textNodes = array(); // text nodes which should be deleted
        $childNodes = array(); // other child nodes
        for ($i = 0; $i < $root->childNodes->length; $i++) {
            /** @var \DOMElement $child */
            $child = $root->childNodes->item($i);

            if ($child instanceof \DOMText) {
                $textNodes[] = $child;
                $fullText .= $child->wholeText;
            } elseif ($child instanceof \DOMComment || $child instanceof \DOMElement) {
                $childNodes[] = $child;
            } else {
                // unknown node type. We don't know how to format this
                return;
            }
        }

        $fullText = trim($fullText);
        if (strlen($fullText) > 0) {
            // we contain textelf
            $hasText = true;
        } else {
            $hasText = false;
        }

        $hasChildNode = (count($childNodes) > 0);

        if ($hasText && $hasChildNode) {
            // element contains both text and child nodes - we don't know how to format this one
            return;
        }

        // remove text nodes
        foreach ($textNodes as $node) {
            $root->removeChild($node);
        }

        if ($hasText) {
            // only text - add a single text node to the element with the full text
            $root->appendChild(new \DOMText($fullText));
            return;
        }

        if (!$hasChildNode) {
            // empty node. Nothing to do
            return;
        }

        /* Element contains only child nodes - add indentation before each one, and
         * format child elements.
         */
        $childIndentation = $indentBase.'  ';
        foreach ($childNodes as $node) {
            // add indentation before node
            $root->insertBefore(new \DOMText("\n".$childIndentation), $node);

            // format child elements
            if ($node instanceof \DOMElement) {
                self::formatDOMElement($node, $childIndentation);
            }
        }

        // add indentation before closing tag
        $root->appendChild(new \DOMText("\n".$indentBase));
    }
}