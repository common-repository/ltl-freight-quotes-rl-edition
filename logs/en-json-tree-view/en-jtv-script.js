function en_jtv_create_dom(json, en_jtv_show_generate_button = false) {
  function impl(json, parent) {
      var en_jtv_append_element = (parent, tag) => {
          var e = document.createElement(tag);
          parent.appendChild(e);
          return e;
      };

      var en_jtv_create_element = (tag, en_jtv_class_name, en_jtv_text_content) => {
          var e = document.createElement(tag);
          e.className = en_jtv_class_name;
          if (en_jtv_text_content)
              e.textContent = en_jtv_text_content;
          return e;
      };

      var en_jtv_append_text = (element, text) => {
          element.appendChild(document.createTextNode(text));
      }

      var en_jtv_json_escaped = /\\(?:"|\\|b|f|n|r|t|u[0-1a-fA-F]{4})/;
      switch (typeof (json)) {
          case 'boolean':
          case 'number':
              var str = JSON.stringify(json);
              var e = en_jtv_create_element('span', 'en_jtv_numeric_value', str);
              e.dataset.valueData = str;
              parent.appendChild(e);
              break;
          case 'string':
              var str = JSON.stringify(json);
              var str = str.substring(1, str.length - 1);
              var inner = en_jtv_create_element('span', 'en_jtv_string_value', '"' + str + '"');
              inner.dataset.valueData = str;
              if (en_jtv_json_escaped.test(str)) {
                  var outer = document.createElement('span');
                  outer.appendChild(inner);
                  parent.appendChild(outer);
              } else {
                  parent.appendChild(inner);
              }
              break;
          case 'object':
              if (json === null) {
                  var e = en_jtv_create_element('span', 'en_jtv_show_null_value', 'null');
                  e.dataset.valueData = 'null';
                  parent.appendChild(e);
                  break;
              }

          function en_jtv_show_add_copy_button(element, json) {
              const button = en_jtv_append_element(element, 'div');
              button.className = 'en_jtv_copy';
              button.addEventListener('click', (event) => {
                  const onFail = (e) => {
                      button.classList.add('en_jtv_not_copied');
                      void button.offsetWidth; // triggers animation transitions
                      button.classList.remove('en_jtv_not_copied');
                      console.log('Failed to copy to clipboard: ', e);
                  };
                  try {
                      navigator.clipboard.writeText(JSON.stringify(json, null, '  '))
                          .then(
                              () => {
                                  button.classList.add('en_jtv_copied');
                                  void button.offsetWidth; // triggers animation transitions
                                  button.classList.remove('en_jtv_copied');
                              },
                              onFail
                          );
                  } catch (e) {
                      onFail(e.message);
                  }
              });
          }

          function en_jtv_show_create_number_of_elements(count) {
              var e = en_jtv_create_element('span', 'en_jtv_number_of_elements');
              e.dataset.itemCount = count;
              return e;
          }

              var isArray = Array.isArray(json);
              if (isArray) {
                  if (json.length == 0) {
                      en_jtv_append_text(parent, '[]');
                      break;
                  }
                  en_jtv_append_text(parent, '[');
                  var list = en_jtv_append_element(parent, 'ul');
                  var item = null;
                  for (var i = 0; i != json.length; ++i) {
                      if (item)
                          en_jtv_append_text(item, ',');
                      item = document.createElement('li');
                      var outer = en_jtv_append_element(item, 'div');
                      outer.className = 'en_key';
                      const value = json[i];
                      en_jtv_append_element(outer, 'span');
                      if (en_jtv_show_generate_button)
                          en_jtv_show_add_copy_button(outer, value);
                      impl(value, item);
                      list.appendChild(item);
                  }
                  parent.appendChild(en_jtv_show_create_number_of_elements(json.length));
                  en_jtv_append_text(parent, ']');
              } else {
                  var keys = Object.keys(json);
                  if (keys.length == 0) {
                      en_jtv_append_text(parent, '{}');
                      break;
                  }
                  en_jtv_append_text(parent, '{');
                  var list = en_jtv_append_element(parent, 'ul');
                  var item = null;
                  for (var key of keys) {
                      if (item)
                          en_jtv_append_text(item, ',');
                      item = document.createElement('li');
                      var outer = en_jtv_append_element(item, 'div');
                      outer.className = 'en_key';
                      const value = json[key];
                      var inner = en_jtv_append_element(outer, 'span');
                      if (en_jtv_show_generate_button)
                          en_jtv_show_add_copy_button(outer, value);
                      inner.dataset.keyData = key;
                      inner.textContent = '"' + key + '"';
                      en_jtv_append_text(item, ': ');
                      impl(value, item);
                      list.appendChild(item);
                  }
                  parent.appendChild(en_jtv_show_create_number_of_elements(keys.length));
                  en_jtv_append_text(parent, '}');
              }
              if (parent.tagName == 'LI') {
                  parent.classList.add('en_folder', 'en_folded');
              }
              break;
          default:
              en_jtv_append_text(parent, 'unexpected: ' + JSON.stringify(json));
              break;
      }
  };
  var holder = document.createElement('div');
  holder.className = 'en_jtv';
  impl(json, holder);
  for (var e of holder.querySelectorAll('li.en_folder > div.en_key > span')) {
      e.addEventListener('click', function (event) {
          var parent = this.parentElement.parentElement;
          var expanded = !parent.classList.toggle('en_folded');
          if (event.ctrlKey || event.metaKey) {
              var children = parent.querySelectorAll('li.en_folder');
              if (expanded) {
                  for (var e of children)
                      e.classList.remove('en_folded');
              } else {
                  for (var e of children)
                      e.classList.add('en_folded');
              }
          }
      });
  }

  return holder;
}

function en_jtv_show_data(json) {
  try {
      json = JSON.stringify(json);
      json = JSON.parse(json);
      document.getElementById('en_jtv_parse_error').textContent = '';
  } catch (e) {
      document.getElementById('en_jtv_parse_error').textContent = e.message;
      return;
  }
  var tree = en_jtv_create_dom(json, true);
  var holder = document.getElementById('en_res_popup');
  holder.removeChild(holder.querySelector('*'));
  holder.appendChild(tree);
}