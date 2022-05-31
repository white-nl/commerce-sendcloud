<?php


namespace white\commerce\sendcloud\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

class BulkPrintSendcloudLabelsAction extends ElementAction
{
    public function getTriggerLabel(): string
    {
        return Craft::t('commerce-sendcloud', "Print Sendcloud Labels");
    }

    public function getTriggerHtml(): ?string
    {
        $type = Json::encode(static::class);

        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: {$type},
        activate: function(\$selectedItems)
        {
            var \$form = Craft.createForm().attr('target', '_blank').appendTo(Garnish.\$bod);
            $(Craft.getCsrfInput()).appendTo(\$form);
            $('<input/>', {
                type: 'hidden',
                name: 'action',
                value: 'commerce-sendcloud/cp/parcel/bulk-print-labels'
            }).appendTo(\$form);
            \$selectedItems.each(function() {
                $('<input/>', {
                    type: 'hidden',
                    name: 'orderIds[]',
                    value: $(this).data('id')
                }).appendTo(\$form);
            });
            $('<input/>', {
                type: 'submit',
                value: 'Submit'
            }).appendTo(\$form);
            \$form.submit();
            \$form.remove();
        }
    });
})();
JS;

        $request = Craft::$app->getRequest();
        $js = str_replace([
            '{csrfName}',
            '{csrfValue}',
        ], [
            $request->csrfParam,
            $request->getCsrfToken(),
        ], $js);

        Craft::$app->getView()->registerJs($js);
        return null;
    }
}
