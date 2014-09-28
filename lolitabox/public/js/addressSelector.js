var Loli = Loli || {};
(function(){

    Loli.AddressSelector = function(options){
        this.options = $.extend({}, this.options, options);
        this.init();
    }

    Loli.AddressSelector.DEFAULT_OPTION_VALUE="-1";

    Loli.AddressSelector.prototype={
        options : {
            containerId : "content",
            provinceSelectName : "userAddress.province_area_id",
            citySelectName : "userAddress.city_area_id",
            districtSelectName : "userAddress.district__area_id",
            getProvincesUrl : null,
            getCitiesUrl : null,
            getDistrictsUrl : null
        },

        init : function(){
            var container = $("#"+this.options.containerId);
            if(!container.length){
                return;
            }
            this.provinceSelector = $("<select>").attr("name", this.options.provinceSelectName).addClass("province").appendTo(container).bind("change", function(){
                var options = $(this).find("option:selected");
                me._renderCityOptions($(this).val());
            });
            this.citySelector = $("<select>").attr("name", this.options.citySelectName).addClass("city").appendTo(container).bind("change", function(){
                var options = $(this).find("option:selected");
                me._renderDistrictOptions($(this).val());
            });
            this.districtSelector = $("<select>").attr("name", this.options.districtSelectName).addClass("district").appendTo(container);
            var me = this;
            $.ajax({
                url:me.options.getProvincesUrl,
                type:"GET",
                datatype:"json",
                success:function(provinces){
                    $.each(provinces, function(index, province){
                        $("<option>").attr("value", province.area_id).text(province.title).appendTo(me.provinceSelector);
                        if(index == 0){
                            me._renderCityOptions(province.area_id);
                        }
                    })
                }
            })
        },

        _renderCityOptions : function(provinceId){
            this.citySelector.empty();
            this.districtSelector.empty();
            var me = this;
            var cityJson = null;
            if(provinceId != Loli.AddressSelector.DEFAULT_OPTION_VALUE){
                $.ajax({
                    url:me.options.getCitiesUrl,
                    type:"GET",
                    datatype:"json",
                    data : {"provinceId":provinceId},
                    success:function(cities){
                        cityJson = cities;
                        $.each(cities, function(index, city){
                            $("<option>").attr("value", city.area_id).text(city.title).appendTo(me.citySelector);
                            if(index == 0){
                                me._renderDistrictOptions(city.area_id);
                            }
                        })
                    }
                })
            }
        },

        _renderDistrictOptions : function(cityId){
            this.districtSelector.empty();
            var me = this;
            $.ajax({
                url:me.options.getDistrictsUrl,
                type:"GET",
                datatype:"json",
                data : {"cityId":cityId},
                success:function(districts){
                    $.each(districts, function(index, district){
                        $("<option>").attr("value", district.area_id).text(district.title).appendTo(me.districtSelector);
                    })
                }
            })
        }

    }
})()