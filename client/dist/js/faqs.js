(function ($) {
    "use strict";
    /*global jQuery, document, window*/

    $(document).ready(function () {

        //we use aria-expanded to count only on opening of the FAQ
        //aria-expanded will be set to true before the click event is fired
        $(document).on('click', 'a[data-voteurl][aria-expanded="true"]', function (e) {
            e.preventDefault();
            $.get($(this).attr('data-voteurl'));
        });

    });

}(jQuery));
