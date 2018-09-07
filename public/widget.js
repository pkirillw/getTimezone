var timeZonesLibrary = {};
var apiServerTimezone = 'https://timezone.pkirillw.ru/public/';
timeZonesLibrary.api = function (url, callbackSuccess, callbackError) {
    console.time('[API] ' + url);
    $.ajax({
        type: 'GET',
        async: false,
        url: apiServerTimezone + '' + url,
        crossDomain: true,
        dataType: 'json',
        success: callbackSuccess,
        error: callbackError
    });
    console.timeEnd('[API] ' + url);
}

window.timeZoneWidget.render.push(function (widget) {
    var area = widget.system().area;
    var usersAvaible = [1159269, 1517371];

    //if (usersAvaible.indexOf(AMOCRM.data.current_card.user.id) == -1) {
    //    return true;
    //}

    console.log('Timezones Init!');
    var stylePlus = '' +
        '<style>' +
        '.js-get-timezone {' +
        '    position: absolute;\n' +
        '    right: 15px;\n' +
        '    top: 6px;\n' +
        '    padding: 1px;\n' +
        '    border: 1px solid #4077d6;\n' +
        '    color: #fff;\n' +
        '    border-radius: 4px;\n' +
        '    background: #4c8bf7;\n' +
        '    cursor: alias;' +
        '    font-weight: bold;' +
        '}' +
        '</style>';
    $('head').append(stylePlus);
    if (area == 'lcard') {
        $(document).on('ntrt:provider_tab:render', function () {
            console.log('ntrt:provider_tab:render');
            $('[name="ntCFV[1]"]').parent().after('<span class="js-get-timezone">МСК+?</span>');
        });
    }
    $('[name="CFV[1970926]"]').parent().after('<span class="js-get-timezone">МСК+?</span>');
    console.log('Click handle on!');
    $(document).on('click', '.js-get-timezone', function () {
        var area = self.system().area,
            id = AMOCRM.data.current_card.id;

        if ($(this).parent().parent().parent().parent().find('[name="ELEMENT_TYPE"]').val() === "1") {
            area = 'ccard';
            id = $(this).parent().parent().parent().parent().find('[name="ID"]').val();
        } else if ($(this).parent().parent().parent().parent().find('[name="ELEMENT_TYPE"]').val() === "2") {
            area = 'comcard';
            id = $(this).parent().parent().parent().parent().find('[name="ID"]').val();
        }

        timeZonesLibrary.api(
            'getTimezone/' + id + '/' + area,
            function (data) {
                if (data.error != undefined) {
                    var notification = {
                        text: {
                            header: "Ошибка Timezones",
                            text: ''
                        },
                        type: "error"
                    };
                    if (data.error == '') {
                        notification.text.text = 'Часовой пояс не определен';
                    } else {
                        notification.text.text = data.error;
                    }
                    AMOCRM.notifications.show_notification(notification);
                } else {
                    $('input[name="CFV[1970926]"]').val('');
                    setTimeout(function () {
                        $('input[name="CFV[1970926]"]').val(data.data);
                    }, 250);
                }

            },
            function (data) {
                var notification = {
                    text: {
                        header: "Ошибка Timezones",
                        text: "Неизвестная ошибка"
                    },
                    type: "error"
                };
                AMOCRM.notifications.show_notification(notification);
            }
        );
    });

    return true;
})
;
window.timeZoneWidget.init.push(function () {
    return true;
});
window.timeZoneWidget.bind_actions.push(function () {
    return true;
});
window.timeZoneWidget.settings.push(function () {
    return true;
});
window.timeZoneWidget.onSave.push(function () {
    return true;
});
window.timeZoneWidget.destroy.push(function () {
    $(document).off('click', '.js-get-timezone');
    return true;
});
window.timeZoneWidget.contacts.push(function () {
    return true;
});
window.timeZoneWidget.leads.push(function () {
    return true;
});
window.timeZoneWidget.tasks.push(function () {
    return true;
});


