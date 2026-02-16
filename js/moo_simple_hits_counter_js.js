// send request to server to save visitor/view after 1 second of page load
jQuery(function () {
	// console.log(document.cookie);
	jQuery.ajax({
		type:'POST',
		data:{action:'moo_update_counter'},
		url: templateUrl+"/wp-admin/admin-ajax.php?object_id="+tracked_object_id+"&object_type="+encodeURIComponent(tracked_object_type),
		success: function(value) {
		}
	});
});
