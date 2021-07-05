<?php


namespace white\commerce\sendcloud\elements\actions;


use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

class BulkPushToSendcloudAction extends ElementAction
{
    public function getTriggerLabel(): string
    {
        return Craft::t('commerce-sendcloud', "Push to Sendcloud");
    }

    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: {$type},
        activate: function(\$selectedItems)
        {
            var \$form = Craft.createForm().appendTo(Garnish.\$bod);
            $(Craft.getCsrfInput()).appendTo(\$form);
            $('<input/>', {
                type: 'hidden',
                name: 'action',
                value: 'commerce-sendcloud/cp/parcel/bulk-push'
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