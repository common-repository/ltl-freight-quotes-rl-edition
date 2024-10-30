<div id="en_jtv_showing_res" class="overlay">
    <div class="popup">
        <h2>Response</h2>
        <a class="close" href="#">&times;</a>
        <div class="content">
            <div>
                <span id="en_jtv_parse_error" style="color: darkred;"></span>
            </div>
            <div id="en_res_popup"></div>
            <script type="text/javascript">
                function en_jtv_res_detail(json) {
                    jQuery("#en_res_popup").empty();
                    var tree = en_jtv_create_dom(json, true);
                    document.getElementById('en_res_popup').appendChild(tree);
                    en_jtv_show_data(json);
                }
            </script>
        </div>
    </div>
</div>

