(function ($) {
    function refreshCounters() {
        $('.efc-pro-counter, .efc-pro-progress').each(function () {
            var $el = $(this);
            var slug = $el.data('efc-slug');
            if (!slug) return;

            $.post(EFCPro_Ajax.ajax_url, { action: 'efc_pro_get_count', slug: slug, nonce: EFCPro_Ajax.nonce }, function (resp) {
                if (resp && resp.success && resp.data && typeof resp.data.count !== 'undefined') {
                    var count = parseInt(resp.data.count, 10);

                    // Handle animated numeric counters
                    if ($el.hasClass('efc-pro-animated')) {
                        var start = 0; // always start from 0 on page load
                        var end = count;

                        if (end !== start) {
                            // Inverse duration: smaller numbers animate slower, bigger numbers faster
                            var duration = Math.max(300, 5000 / Math.max(end, 1));

                            $({ c: start }).animate({ c: end }, {
                                duration: duration,
                                easing: 'swing',
                                step: function (now) {
                                    $el.text(Math.floor(now).toLocaleString());
                                },
                                complete: function () {
                                    $el.text(end.toLocaleString());
                                }
                            });
                        }
                    }
                    // Handle progress bar counters
                    else if ($el.hasClass('efc-pro-progress')) {
                        var target = parseInt($el.data('efc-target'), 10) || 0;
                        if (target > 0) {
                            var percent = Math.min(100, Math.round((count / target) * 100));
                            $el.find('.efc-pro-fill').css('width', percent + '%');
                            $el.find('.efc-pro-label').text(count.toLocaleString() + ' / ' + target.toLocaleString());
                        }
                    }
                    // Default plain counter
                    else {
                        $el.text(count.toLocaleString());
                    }
                }
            });
        });
    }

    $(document).ready(function () {
        refreshCounters();
        // Listen to a custom event that Elementor forms might trigger on success
        $(document).on('submit_success', function () { setTimeout(refreshCounters, 500); });
    });
})(jQuery);
