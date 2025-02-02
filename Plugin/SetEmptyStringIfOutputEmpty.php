<?php

namespace CSSoft\Core\Plugin;

class SetEmptyStringIfOutputEmpty
{
    /**
     * After plugin for toHtml method of layout block (applicable for any block)
     *
     * @param  \Magento\Framework\View\Element\AbstractBlock $subject
     * @param  string $resultHtml
     * @return string
     */
    public function afterToHtml(
        \Magento\Framework\View\Element\AbstractBlock $subject,
        $resultHtml
    ) {
        if (!is_string($resultHtml)) {
            return $resultHtml;
        }
        $html = trim($resultHtml, " \n");
        return empty($html) ? $html : $resultHtml;
    }
}
