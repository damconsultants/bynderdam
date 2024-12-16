var config = {
    paths: {
        'bynderjs': 'DamConsultants_BynderDAM/js/bynder',
        'select2': 'DamConsultants_BynderDAM/js/select2'
    },
    shim: {
        'bynderjs': {
            deps: ['jquery']
        },
        'select2': {
            deps: ['jquery']
        },
    },
	map: {
        '*': {
            /*'Magento_PageBuilder/template/form/element/uploader/preview/image.html': 'DamConsultants_Idex/template/form/element/uploader/preview/image.html',
            'Magento_PageBuilder/template/form/element/uploader/image.html': 'DamConsultants_Idex/template/form/element/uploader/image.html',*/
            'Magento_PageBuilder/template/form/element/html-code.html': 'DamConsultants_BynderDAM/template/form/element/html-code.html',
            /*'Magento_PageBuilder/js/form/element/image-uploader': 'DamConsultants_Idex/js/form/element/image-uploader',*/
            'Magento_PageBuilder/js/form/element/html-code': 'DamConsultants_BynderDAM/js/form/element/html-code',
        },
    }
};