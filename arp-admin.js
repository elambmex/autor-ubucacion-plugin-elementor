jQuery(document).ready(function($){
    // Al pulsar Quick Edit: rellenar campos con los datos actuales
    var wp_inline_edit = inlineEditPost.edit;
    inlineEditPost.edit = function( post_id ) {
        wp_inline_edit.apply(this, arguments);
        var id = 0;
        if (typeof(post_id) == 'object') {
            id = parseInt(this.getId(post_id));
        }
        if (id > 0) {
            var row = $('#post-' + id);
            var prefijo = row.find('td.column-arp_ubicacion').data('arp-prefijo') || '';
            // Pero podemos obtener el texto mostrado y tratarlo:
            var text = row.find('td.column-arp_ubicacion').text().trim();
            // Rellenar los campos del quick edit
            var $edit_row = $('#edit-' + id);
            // Si no encontramos el zone, rellenamos el area del quick edit global
            $('#edit-' + id).find('select[name="arp_ubicacion_prefijo_qe"]').val('');
            $('#edit-' + id).find('input[name="arp_ubicacion_lugar_qe"]').val('');
            // Alternativa simple: si text tiene espacio, separar primer palabra como prefijo y resto lugar
            if (text && text !== '—') {
                var parts = text.split(' ');
                // Buscamos si el primer token coincide con una de las opciones
                var opciones = ['Reportando','Reportó','Reportaron','En','Desde'];
                var first = parts[0];
                var pref = '';
                var lugar = text;
                // Buscar coincidencia prefijo exacta o "En vivo desde"
                var known = ['Reportando','Reportó','Reportaron','En vivo desde','Desde'];
                for (var i=0;i<known.length;i++){
                    if (text.indexOf(known[i]) === 0) {
                        pref = known[i];
                        lugar = text.replace(known[i],'').trim();
                        break;
                    }
                }
                if (pref) {
                    $('#edit-' + id).find('select[name="arp_ubicacion_prefijo_qe"]').val(pref);
                    $('#edit-' + id).find('input[name="arp_ubicacion_lugar_qe"]').val(lugar);
                } else {
                    $('#edit-' + id).find('input[name="arp_ubicacion_lugar_qe"]').val(text);
                }
            }
        }
    };

    // Cuando se abre quick edit en la lista desde el botón "Quick Edit" estándar,
    // WordPress clona la fila .inline-edit-row; por eso usamos evento click
    $(document).on('click', '.editinline', function(){
        var row = $(this).closest('tr');
        var post_id = row.attr('id').replace('post-','');
        var text = row.find('td.column-arp_ubicacion').text().trim();
        var $inline = $('#edit-'+post_id);
        if (!$inline.length) {
            // quick edit global
            var $qe = $('#the-list').find('tr.inline-edit-row');
            $qe.find('select[name="arp_ubicacion_prefijo_qe"]').val('');
            $qe.find('input[name="arp_ubicacion_lugar_qe"]').val('');
            if (text && text !== '—') {
                // intentar separar
                var known = ['Reportando','Reportó','Reportaron','En vivo desde','Desde'];
                var pref = '';
                var lugar = text;
                for (var i=0;i<known.length;i++){
                    if (text.indexOf(known[i]) === 0) {
                        pref = known[i];
                        lugar = text.replace(known[i],'').trim();
                        break;
                    }
                }
                if (pref) {
                    $qe.find('select[name="arp_ubicacion_prefijo_qe"]').val(pref);
                    $qe.find('input[name="arp_ubicacion_lugar_qe"]').val(lugar);
                } else {
                    $qe.find('input[name="arp_ubicacion_lugar_qe"]').val(text);
                }
            }
        }
    });
});
