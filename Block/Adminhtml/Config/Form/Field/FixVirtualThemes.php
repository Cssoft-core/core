<?php
namespace CSSoft\Core\Block\Adminhtml\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class FixVirtualThemes extends \Magento\Config\Block\System\Config\Form\Field
{
    public function render(AbstractElement $element)
    {
        $url = $this->getUrl('cssoft/theme/fixVirtualThemes');
        $cacheUrl = $this->getUrl('adminhtml/cache/index');
        $buttonText = __("Fix All");
        return <<<HTML
<tr>
    <td colspan="100">
        <div class="button-container">
            <button id="fix-all-themes" 
                class="button action-configure" 
                type="button"
                ><span>$buttonText</span>
            </button>
        </div>

        <script type="text/javascript">
            require([
                'CSSoft_Core/js/virtualfix'
            ], function (virtualfix) {
                virtualfix.init("$url", "$cacheUrl", '#fix-all-themes');
            });
        </script>

    </td>
</tr>
HTML;
    }
}
