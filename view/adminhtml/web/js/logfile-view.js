define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return function (config) {
        var searchUrl       = config.searchUrl,
            logFile         = config.logFile,
            previousLogUrl  = config.previousLogUrl,
            liveLogUrl      = config.liveLogUrl,
            isCompressedLog = config.isCompressedLog,
            MAX_QUERY_LENGTH = 500;

        $(function () {
            var liveLogInterval   = null,
                lastLogSize       = $('#log-output').val().length,
                isLiveLogActive   = false,
                isWrapLines       = false,
                searchMode        = false,
                searchQuery       = '',
                searchEarliestLine = 0,
                searchHasMore     = false,
                preSearchContent  = '',
                preSearchBtnDisabled = false,
                preSearchBtnStart = 0;

            // --- Search ---

            function openSearch() {
                preSearchContent     = $('#log-output').val();
                preSearchBtnDisabled = $('#load-previous').prop('disabled');
                preSearchBtnStart    = parseInt($('#load-previous').data('start'), 10);
                $('#log-search-inline').show();
                $('#log-search-toggle').addClass('active');
                $('#log-search-input').focus();
                $('#toggle-live-log').prop('disabled', true);
            }

            function closeSearch() {
                $('#log-search-inline').hide();
                $('#log-search-input').val('');
                $('#log-search-info').text('').removeClass('no-match');
                $('#log-search-toggle').removeClass('active');

                if (searchMode) {
                    $('#log-output').val(preSearchContent);
                    $('#load-previous').prop('disabled', preSearchBtnDisabled);
                    $('#load-previous').data('start', preSearchBtnStart);
                }

                searchMode         = false;
                searchQuery        = '';
                searchEarliestLine = 0;
                searchHasMore      = false;

                if (!isLiveLogActive) {
                    $('#toggle-live-log').prop('disabled', isCompressedLog);
                }
            }

            function doSearch() {
                var query = $.trim($('#log-search-input').val());

                if (!query) {
                    return;
                }

                if (query.length > MAX_QUERY_LENGTH) {
                    $('#log-search-info')
                        .text($t('Search query is too long (max 500 characters).'))
                        .addClass('no-match');
                    return;
                }

                searchQuery = query;
                $('#log-search-btn').prop('disabled', true);

                $.ajax({
                    url: searchUrl,
                    showLoader: true,
                    data: {
                        file: logFile,
                        query: query,
                        form_key: window.FORM_KEY
                    },
                    success: function (res) {
                        var matches;

                        $('#log-search-btn').prop('disabled', false);

                        if (res.success) {
                            matches            = res.matches || [];
                            searchMode         = true;
                            searchEarliestLine = res.earliest_line || 0;
                            searchHasMore      = res.has_more || false;

                            $('#log-output').val(matches.map(function (m) {
                                return m.content;
                            }).join('\n'));
                            $('#load-previous').prop('disabled', !searchHasMore);

                            if (matches.length === 0) {
                                $('#log-search-info').text($t('No matches found')).addClass('no-match');
                            } else if (searchHasMore) {
                                $('#log-search-info').text($t('Showing last 20 results')).removeClass('no-match');
                            } else {
                                $('#log-search-info')
                                    .text($t('Found') + ' ' + matches.length + ' ' + $t('match(es)'))
                                    .removeClass('no-match');
                            }
                        } else {
                            $('#log-search-info').text(res.message || $t('Search error')).addClass('no-match');
                        }
                    },
                    error: function () {
                        $('#log-search-btn').prop('disabled', false);
                        $('#log-search-info').text($t('Search error')).addClass('no-match');
                    }
                });
            }

            function loadPreviousSearch($btn) {
                $.ajax({
                    url: searchUrl,
                    showLoader: true,
                    data: {
                        file: logFile,
                        query: searchQuery,
                        before_line: searchEarliestLine,
                        form_key: window.FORM_KEY
                    },
                    success: function (res) {
                        var $out, newLines;

                        if (res.success && res.matches && res.matches.length) {
                            newLines           = res.matches.map(function (m) { return m.content; }).join('\n');
                            $out               = $('#log-output');
                            $out.val(newLines + '\n' + $out.val());
                            searchEarliestLine = res.earliest_line || 0;
                            searchHasMore      = res.has_more || false;
                            $btn.prop('disabled', !searchHasMore);
                        } else {
                            $btn.prop('disabled', true);
                        }
                    },
                    error: function () {
                        $btn.prop('disabled', true);
                    }
                });
            }

            // --- Live log ---

            function startLiveLog($btn) {
                isLiveLogActive = true;
                $btn.find('.icon-live-log-play').hide();
                $btn.find('.icon-live-log-pause').show();
                $btn.find('.live-log-btn-text').text($t('Stop Live Log'));
                $btn.addClass('active');
                $('#live-log-status').show().addClass('active');
                $('#log-search-toggle').prop('disabled', true);
                liveLogInterval = setInterval(checkForNewLogs, 3000);
            }

            function stopLiveLog($btn) {
                isLiveLogActive = false;
                $btn.find('.icon-live-log-play').show();
                $btn.find('.icon-live-log-pause').hide();
                $btn.find('.live-log-btn-text').text($t('Start Live Log'));
                $btn.removeClass('active');
                $('#live-log-status').hide().removeClass('active');
                $('#log-search-toggle').prop('disabled', false);

                if (liveLogInterval) {
                    clearInterval(liveLogInterval);
                    liveLogInterval = null;
                }
            }

            function checkForNewLogs() {
                $.ajax({
                    url: liveLogUrl,
                    data: {
                        file: logFile,
                        last_size: lastLogSize,
                        form_key: window.FORM_KEY
                    },
                    success: function (res) {
                        var $textarea, newContent;

                        if (res.success && res.new_content) {
                            $textarea  = $('#log-output');
                            newContent = $textarea.val() + res.new_content;
                            $textarea.val(newContent);
                            lastLogSize = newContent.length;
                            $textarea.scrollTop($textarea[0].scrollHeight);
                        }
                    },
                    error: function () {
                        stopLiveLog($('#toggle-live-log'));
                    }
                });
            }

            // --- Event bindings ---

            $('#log-search-toggle').on('click', function () {
                if ($('#log-search-inline').is(':visible')) {
                    closeSearch();
                } else {
                    openSearch();
                }
            });

            $('#log-search-btn').on('click', doSearch);

            $('#log-search-input').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doSearch();
                } else if (e.key === 'Escape') {
                    closeSearch();
                }
            });

            $(document).on('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();

                    if ($('#log-search-inline').is(':visible')) {
                        $('#log-search-input').focus().select();
                    } else {
                        openSearch();
                    }
                }
            });

            $('#load-previous').on('click', function () {
                var $btn         = $(this),
                    file, start, displayLines;

                if (searchMode) {
                    loadPreviousSearch($btn);
                    return;
                }

                file         = $btn.data('file');
                start        = parseInt($btn.data('start'), 10);
                displayLines = parseInt($btn.data('display-lines'), 10);

                $.ajax({
                    url: previousLogUrl,
                    showLoader: true,
                    data: {
                        file: file,
                        offset: start,
                        lines: displayLines,
                        form_key: window.FORM_KEY
                    },
                    success: function (res) {
                        if (res.success) {
                            if (res.data.trim()) {
                                $('#log-output').prepend(res.data + '\n');
                                $btn.data('start', start + displayLines);
                                lastLogSize = $('#log-output').val().length;
                            }

                            if (!res.has_more) {
                                $btn.prop('disabled', true);
                            }
                        }
                    }
                });
            });

            $('#toggle-live-log').on('click', function () {
                var $btn = $(this);

                if (isLiveLogActive) {
                    stopLiveLog($btn);
                } else {
                    startLiveLog($btn);
                }
            });

            $('#clear-log-view').on('click', function () {
                if (searchMode) {
                    closeSearch();
                }
                $('#log-output').val('');
            });

            $('#toggle-wrap-lines').on('click', function () {
                var $textarea = $('#log-output');

                isWrapLines = !isWrapLines;

                if (isWrapLines) {
                    $textarea.css('white-space', 'pre-wrap');
                    $(this).attr('title', $t('Unwrap Lines')).attr('aria-label', $t('Unwrap Lines')).addClass('active');
                } else {
                    $textarea.css('white-space', 'pre');
                    $(this).attr('title', $t('Wrap Lines')).attr('aria-label', $t('Wrap Lines')).removeClass('active');
                }
            });

            $(window).on('beforeunload', function () {
                if (liveLogInterval) {
                    clearInterval(liveLogInterval);
                }
            });
        });
    };
});
