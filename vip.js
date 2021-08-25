/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 vip plugin for GLPI
 Copyright (C) 2009-2016 by the vip Development Team.

 https://github.com/InfotelGLPI/vip
 -------------------------------------------------------------------------

 LICENSE

 This file is part of vip.

 vip is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 vip is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with vip. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

(function ($) {
    $.fn.initVipPlugin = function (options) {

        var object = this;
        init();

        // Start the plugin
        function init() {
            object.params = new Array();
            object.params['entities_id'] = 0;
            object.params['page_limit'] = 0;
            object.params['minimumResultsForSearch'] = 0;
            object.params['root_doc'] = null;
            object.params['emptyValue'] = null;

            if (options != undefined) {
                $.each(options, function (index, val) {
                    if (val != undefined && val != null) {
                        object.params[index] = val;
                    }
                });
            }
        }

        this.changeRequesterColor = function (vip) {
            $(document).ready(function () {

                // only in ticket form
                if (location.pathname.indexOf('ticket.form.php') > 0) {
                    $.urlParam = function (url, name) {
                        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
                        if (results != null) {
                            return results[1] || 0;
                        }
                    };

                    // get item id
                    var items_id = $.urlParam(window.location.href, 'id');

                    // Launched on each complete Ajax load
                    $(document).ajaxComplete(function (event, xhr, option) {
                        setTimeout(function () {
                            var alreadyVip = false;
                            if ($('#vip_img').length > 0) {
                                alreadyVip = true;
                            }

                            // Get the right tab
                            var tab, inputName;
                            if (items_id > 0) {
                                tab = 'dropdownItilActors';
                                inputName = '_itil_requester[users_id]';
                            } else {
                                tab = 'ticketassigninformation.php';
                                inputName = '_users_id_requester';
                            }

                            // We execute the code only if the ticket form display request is done
                            if ((option.url !== undefined
                                    && option.url.indexOf("ajax/" + tab) > 0
                                    && ((option.data !== undefined && option.data.indexOf("user") > 0 && option.data.indexOf("requester") > 0)
                                        || (option.data !== undefined && option.data.indexOf("users_id_assign") == 0 )
                                        || option.data === undefined))
                            ) {
                                // Get ticket informations
                                $.ajax({
                                    url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/ticket.php',
                                    type: "POST",
                                    dataType: "json",
                                    data: {
                                        'items_id': items_id,
                                        'action': 'getTicket'
                                    },
                                    success: function (response, opts) {
                                        // Replace requester dropdown select2
                                        $("select[name='" + inputName + "']").select2({
                                            width: '80%',
                                            minimumInputLength: 0,
                                            quietMillis: 100,
                                            minimumResultsForSearch: object.params['minimumResultsForSearch'],
                                            ajax: {
                                                url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/getDropdownUsers.php',
                                                dataType: 'json',
                                                type: 'POST',
                                                data: function (term, page) {
                                                    return {
                                                        all: 0,
                                                        right: 'all',
                                                        used: response.used,
                                                        entity_restrict: response.entities_id,
                                                        searchText: term.term,
                                                        page_limit: object.params['page_limit'], // page size
                                                        page: page, // page number
                                                    };
                                                },
                                                results: function (data, page) {
                                                    var more = (data.count >= object.params['page_limit']);
                                                    return {results: data.results, more: more};
                                                }
                                            },
                                            initSelection: function (element, callback) {

                                                var id = $(element).val();
                                                var defaultid = '0';
                                                if (id !== '') {
                                                    // No ajax call for first item
                                                    if (id === defaultid) {
                                                        var data = {
                                                            id: 0,
                                                            text: object.params['emptyValue']
                                                        };
                                                        callback(data);
                                                    } else {
                                                        $.ajax(CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/getDropdownUsers.php', {
                                                            data: {
                                                                all: 0,
                                                                right: "all",
                                                                used: [],
                                                                entity_restrict: response.entities_id,
                                                                _one_id: id
                                                            },
                                                            dataType: 'json',
                                                            type: 'POST',
                                                        }).done(function (data) {
                                                            callback(data);
                                                        });
                                                    }
                                                }
                                            },

                                           templateResult: function (result, container) {
                                              // Red if VIP
                                              $.each(vip, function (index2, val2) {
                                                 if (result.id == val2.id) {
                                                    $(container).css({'color': 'red'});
                                                 }
                                              });

                                              if (result.level) {
                                                 var a = '';
                                                 var i = result.level;
                                                 while (i > 1) {
                                                    a = a + '&nbsp;&nbsp;&nbsp;';
                                                    i = i - 1;
                                                 }
                                                 return a + '&raquo;' + result.text;
                                              }

                                              return result.text;
                                           },
                                           templateSelection: function (result, container) {
                                              $(container).css({'color': ''});
                                              // Red if VIP
                                              var ticketVip = false;
                                              $.each(vip, function (index2, val2) {
                                                 if (result.id == val2.id) {
                                                    $(container).css({'color': 'red'});
                                                    ticketVip = true;
                                                 }
                                              });

                                              if (ticketVip && $('#vip_img').length == 0) {
                                                  $("div[class='responsive_hidden actor_title']").append("<br><br><img id='vip_img' src='" + CFG_GLPI.root_doc + "/" + GLPI_PLUGINS_PATH.vip + "/pics/vip.png'>");
                                              } else if (!ticketVip && !alreadyVip) {
                                                 $("#vip_img").remove();
                                              } 
                                              return result.text;
                                           },
                                        });
                                    }
                                });
                            }

                            // Color requesters already added
                            if (option.url !== undefined
                                && items_id > 0
                                && (option.url.indexOf('vip/ajax/loadscripts.php') > 0 || option.url.indexOf('common.tabs.php') > 0)) {

                                setTimeout(function () {
                                    var item_bloc = $("div[class='actor-content'] div[class='actor_row'] i[class*='fa-user']");
                                    if (
                                        item_bloc.length != 0
                                        //&& item_bloc[0].nextSibling.nodeValue != null
                                        &&
                                        $("span[id*='vip_requester']").length == 0) {
                                        $.ajax({
                                            url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/ticket.php',
                                            type: "POST",
                                            dataType: "json",
                                            data: {
                                                'items_id': items_id,
                                                'action': 'getTicket'
                                            },
                                            success: function (response, opts) {
                                                var ticketVip = false;
                                                $.each(vip, function (index, val) {
                                                    $.each(response.used, function (index2, val2) {
                                                         if (item_bloc[index2].nextSibling.nextSibling != null) {
                                                           var requesterText = item_bloc[index2].nextSibling.nextSibling;
                                                           if (val.id == val2
                                                            && requesterText.nodeValue != null
                                                            ) {
                                                               $("<span id='vip_requester" + index2 + "' class='red'>" + requesterText.nodeValue + "</span>").insertAfter(requesterText);
                                                               $(requesterText).remove();
                                                               ticketVip = true;
                                                           }
                                                        }
                                                    });
                                                });
                                                if (ticketVip && $('#vip_img').length == 0) {
                                                    $("div[class='responsive_hidden actor_title']").append("<br><br><img id='vip_img' src='" + CFG_GLPI.root_doc + "/" + GLPI_PLUGINS_PATH.vip + "/pics/vip.png'>");
                                                }
                                            }
                                        });
                                    }
                                }, 500);
                            }
                        }, 500);
                    }, this);
                }

                if (location.pathname.indexOf('printer.form.php') > 0) {
                    $.urlParam = function (url, name) {
                        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
                        if (results != null) {
                            return results[1] || 0;
                        }
                    };

                    // get item id
                    var items_id = $.urlParam(window.location.href, 'id');

                    setTimeout(function () {
                        var alreadyVip = false;
                        if ($('#vip_img').length > 0) {
                            alreadyVip = true;
                        }

                        // Get the right tab
                        var tab, inputName;
                        if (items_id > 0) {
                            tab = 'getDropdownUsers';
                            inputName = 'users_id';
                        } else {
                            tab = 'ticketassigninformation.php';
                            inputName = '_users_id_requester';
                        }

                        // We execute the code only if the ticket form display request is done

                            // Get ticket informations
                            $.ajax({
                                url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/ticket.php',
                                type: "POST",
                                dataType: "json",
                                data: {
                                    'items_id': items_id,
                                    'action': 'getPrinter'
                                },
                                success: function (response, opts) {
                                    // Replace requester dropdown select2
                                    $("select[name='" + inputName + "']").select2({
                                        width: '80%',
                                        minimumInputLength: 0,
                                        quietMillis: 100,
                                        minimumResultsForSearch: object.params['minimumResultsForSearch'],
                                        ajax: {
                                            url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/getDropdownUsers.php',
                                            dataType: 'json',
                                            type: 'POST',
                                            data: function (term, page) {
                                                return {
                                                    all: 0,
                                                    right: 'all',
                                                    used: response.used,
                                                    entity_restrict: response.entities_id,
                                                    searchText: term.term,
                                                    page_limit: object.params['page_limit'], // page size
                                                    page: page, // page number
                                                };
                                            },
                                            results: function (data, page) {
                                                var more = (data.count >= object.params['page_limit']);
                                                return {results: data.results, more: more};
                                            }
                                        },
                                        initSelection: function (element, callback) {

                                            var id = $(element).val();
                                            var defaultid = '0';
                                            if (id !== '') {
                                                // No ajax call for first item
                                                if (id === defaultid) {
                                                    var data = {
                                                        id: 0,
                                                        text: object.params['emptyValue']
                                                    };
                                                    callback(data);
                                                } else {
                                                    $.ajax(CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/getDropdownUsers.php', {
                                                        data: {
                                                            all: 0,
                                                            right: "all",
                                                            used: [],
                                                            entity_restrict: response.entities_id,
                                                            _one_id: id
                                                        },
                                                        dataType: 'json',
                                                        type: 'POST',
                                                    }).done(function (data) {
                                                        callback(data);
                                                    });
                                                }
                                            }
                                        },

                                        templateResult: function (result, container) {
                                            // Red if VIP
                                            $.each(vip, function (index2, val2) {
                                                if (result.id == val2.id) {
                                                    $(container).css({'color': 'red'});
                                                }
                                            });

                                            if (result.level) {
                                                var a = '';
                                                var i = result.level;
                                                while (i > 1) {
                                                    a = a + '&nbsp;&nbsp;&nbsp;';
                                                    i = i - 1;
                                                }
                                                return a + '&raquo;' + result.text;
                                            }

                                            return result.text;
                                        },
                                        templateSelection: function (result, container) {
                                            $(container).css({'color': ''});
                                            // Red if VIP
                                            var ticketVip = false;
                                            $.each(vip, function (index2, val2) {
                                                if (result.id == val2.id) {
                                                    $(container).css({'color': 'red'});
                                                    ticketVip = true;
                                                }
                                            });

                                            if (ticketVip && $('#vip_img').length == 0) {
                                                $("select[name='" + inputName + "']").parent("td").siblings('td').first();
                                                $("select[name='" + inputName + "']").parent("td").siblings('td').first().append("&nbsp;<span><img id='vip_img' src='" + CFG_GLPI.root_doc + "/" + GLPI_PLUGINS_PATH.vip + "/pics/vip.png'></span>");
                                            } else if (!ticketVip && !alreadyVip) {
                                                $("#vip_img").remove();
                                            }
                                            return result.text;
                                        },
                                    });
                                }
                            });


                        // Color requesters already added

                            setTimeout(function () {
                                var item_bloc = $("td[class='testVIP']");
                                if (
                                    item_bloc.length != 0
                                    //&& item_bloc[0].nextSibling.nodeValue != null
                                    &&
                                    $("span[id*='vip_requester']").length == 0) {
                                    $.ajax({
                                        url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/ticket.php',
                                        type: "POST",
                                        dataType: "json",
                                        data: {
                                            'items_id': items_id,
                                            'action': 'getPrinter'
                                        },
                                        success: function (response, opts) {
                                            var ticketVip = false;
                                            $.each(vip, function (index, val) {
                                                $.each(response.used, function (index2, val2) {
                                                    if (item_bloc[0] != null) {
                                                        var requesterText = item_bloc[0];
                                                        if (val.id == val2
                                                            && requesterText.nodeValue != null
                                                        ) {
                                                            $("<span id='vip_requester" + index2 + "' class='red'>" + requesterText.nodeValue + "</span>").insertAfter(requesterText);
                                                            $(requesterText).remove();
                                                            ticketVip = true;
                                                        }
                                                    }
                                                });
                                            });
                                            if (ticketVip && $('#vip_img').length == 0) {
                                                $("td.testVIP").append("&nbsp;<span><img id='vip_img' src='" + CFG_GLPI.root_doc + "/" + GLPI_PLUGINS_PATH.vip + "/pics/vip.png'></span>");

                                            }
                                        }
                                    });
                                }
                            }, 500);

                    }, 500);

                }
                if (location.pathname.indexOf('computer.form.php') > 0) {
                    $.urlParam = function (url, name) {
                        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
                        if (results != null) {
                            return results[1] || 0;
                        }
                    };

                    // get item id
                    var items_id = $.urlParam(window.location.href, 'id');

                    setTimeout(function () {
                        var alreadyVip = false;
                        if ($('#vip_img').length > 0) {
                            alreadyVip = true;
                        }

                        // Get the right tab
                        var tab, inputName;
                        if (items_id > 0) {
                            tab = 'getDropdownUsers';
                            inputName = 'users_id';
                        } else {
                            tab = 'ticketassigninformation.php';
                            inputName = '_users_id_requester';
                        }

                        // We execute the code only if the ticket form display request is done

                            // Get ticket informations
                            $.ajax({
                                url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/ticket.php',
                                type: "POST",
                                dataType: "json",
                                data: {
                                    'items_id': items_id,
                                    'action': 'getComputer'
                                },
                                success: function (response, opts) {
                                    // Replace requester dropdown select2
                                    $("select[name='" + inputName + "']").select2({
                                        width: '80%',
                                        minimumInputLength: 0,
                                        quietMillis: 100,
                                        minimumResultsForSearch: object.params['minimumResultsForSearch'],
                                        ajax: {
                                            url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/getDropdownUsers.php',
                                            dataType: 'json',
                                            type: 'POST',
                                            data: function (term, page) {
                                                return {
                                                    all: 0,
                                                    right: 'all',
                                                    used: response.used,
                                                    entity_restrict: response.entities_id,
                                                    searchText: term.term,
                                                    page_limit: object.params['page_limit'], // page size
                                                    page: page, // page number
                                                };
                                            },
                                            results: function (data, page) {
                                                var more = (data.count >= object.params['page_limit']);
                                                return {results: data.results, more: more};
                                            }
                                        },
                                        initSelection: function (element, callback) {

                                            var id = $(element).val();
                                            var defaultid = '0';
                                            if (id !== '') {
                                                // No ajax call for first item
                                                if (id === defaultid) {
                                                    var data = {
                                                        id: 0,
                                                        text: object.params['emptyValue']
                                                    };
                                                    callback(data);
                                                } else {
                                                    $.ajax(CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/getDropdownUsers.php', {
                                                        data: {
                                                            all: 0,
                                                            right: "all",
                                                            used: [],
                                                            entity_restrict: response.entities_id,
                                                            _one_id: id
                                                        },
                                                        dataType: 'json',
                                                        type: 'POST',
                                                    }).done(function (data) {
                                                        callback(data);
                                                    });
                                                }
                                            }
                                        },

                                        templateResult: function (result, container) {
                                            // Red if VIP
                                            $.each(vip, function (index2, val2) {
                                                if (result.id == val2.id) {
                                                    $(container).css({'color': 'red'});
                                                }
                                            });

                                            if (result.level) {
                                                var a = '';
                                                var i = result.level;
                                                while (i > 1) {
                                                    a = a + '&nbsp;&nbsp;&nbsp;';
                                                    i = i - 1;
                                                }
                                                return a + '&raquo;' + result.text;
                                            }

                                            return result.text;
                                        },
                                        templateSelection: function (result, container) {
                                            $(container).css({'color': ''});
                                            // Red if VIP
                                            var ticketVip = false;
                                            $.each(vip, function (index2, val2) {
                                                if (result.id == val2.id) {
                                                    $(container).css({'color': 'red'});
                                                    ticketVip = true;
                                                }
                                            });

                                            if (ticketVip && $('#vip_img').length == 0) {
                                                $("select[name='" + inputName + "']").parent("td").siblings('td').first();
                                                $("select[name='" + inputName + "']").parent("td").siblings('td').first().append("&nbsp;<span><img id='vip_img' src='" + CFG_GLPI.root_doc + "/" + GLPI_PLUGINS_PATH.vip + "/pics/vip.png'></span>");
                                            } else if (!ticketVip && !alreadyVip) {
                                                $("#vip_img").remove();
                                            }
                                            return result.text;
                                        },
                                    });
                                }
                            });


                        // Color requesters already added

                            setTimeout(function () {
                                var item_bloc = $("td[class='testVIP']");
                                if (
                                    item_bloc.length != 0
                                    //&& item_bloc[0].nextSibling.nodeValue != null
                                    &&
                                    $("span[id*='vip_requester']").length == 0) {
                                    $.ajax({
                                        url: CFG_GLPI.root_doc+'/'+GLPI_PLUGINS_PATH.vip+'/ajax/ticket.php',
                                        type: "POST",
                                        dataType: "json",
                                        data: {
                                            'items_id': items_id,
                                            'action': 'getPrinter'
                                        },
                                        success: function (response, opts) {
                                            var ticketVip = false;
                                            $.each(vip, function (index, val) {
                                                $.each(response.used, function (index2, val2) {
                                                    if (item_bloc[0] != null) {
                                                        var requesterText = item_bloc[0];
                                                        if (val.id == val2
                                                            && requesterText.nodeValue != null
                                                        ) {
                                                            $("<span id='vip_requester" + index2 + "' class='red'>" + requesterText.nodeValue + "</span>").insertAfter(requesterText);
                                                            $(requesterText).remove();
                                                            ticketVip = true;
                                                        }
                                                    }
                                                });
                                            });
                                            if (ticketVip && $('#vip_img').length == 0) {
                                                $("td.testVIP").append("&nbsp;<span><img id='vip_img' src='" + CFG_GLPI.root_doc + "/" + GLPI_PLUGINS_PATH.vip + "/pics/vip.png'></span>");

                                            }
                                        }
                                    });
                                }
                            }, 500);

                    }, 500);

                }
            });

        }

        return this;
    }
}(jQuery));