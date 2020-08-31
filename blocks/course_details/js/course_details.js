$(function () {
	$('#id_delete, #id_update').closest('.fitem').hide();
	var inputs = ['number', 'name', 'place', 'capacity'];
	
	$('#id_classes').change(function() {
		if(this.value != 'new_class')
		{
			$.post('ajax/classes.php', {id:this.value, func:'find'}, function(data){
				$.each(inputs, function(kay, value){
					$('#id_' + value).val(data[value]);
				});
				
				$('#id_send').closest('.fitem').hide();
				$('#id_delete, #id_update').closest('.fitem').show();
			}, 'json');
		} else {
			$.each(inputs, function(kay, value){
					$('#id_' + value).val('');
			});
			
			$('#id_send').closest('.fitem').show();
			$('#id_delete, #id_update').closest('.fitem').hide();
		}
	});
	
	$('input[name="delete"]').click(function() {
		
		if (check_valid_form())
		{
			alert(M.str.block_course_details.need_insert_all);
		}
		
		$.post('ajax/classes.php', {func:'remove', id:$('#id_classes').val()}, function(data){
			if(data)
			{
				alert(M.str.block_course_details.remove_classroom_succsed);
			} else {
				alert(M.str.block_course_details.error);
			}
			M.core_formchangechecker.stateinformation.formchanged = false;
			location.reload();
		});
	});
	
	$('input[name="update"]').click(function() {
		
		if (check_valid_form())
		{
			alert(M.str.block_course_details.need_insert_all);
		}
		
		$.post('ajax/classes.php', {func:'update', id:$('#id_classes').val(), number:$('#id_number').val(), name:$('#id_name').val(), place:$('#id_place').val(), capacity:$('#id_capacity').val()}, function(data){
			if(data)
			{
				alert(M.str.block_course_details.update_classroom_succsed);
			} else {
				alert(M.str.block_course_details.error);
			}
			M.core_formchangechecker.stateinformation.formchanged = false;
			location.reload();
		});
	});
	
	 $('input[name="send"]').click(function() {
		 
		if (check_valid_form())
		{
			alert(M.str.block_course_details.need_insert_all);
		}
		
		$.post('ajax/classes.php', {func:'add', number:$('#id_number').val(), name:$('#id_name').val(), place:$('#id_place').val(), capacity:$('#id_capacity').val()}, function(data){
			if(data)
			{
				alert(M.str.block_course_details.create_classroom_succsed);
			} else {
				alert(M.str.block_course_details.error);
			}
			M.core_formchangechecker.stateinformation.formchanged = false;
			location.reload();
		});
	 });
	
	function check_valid_form() {
		nosend = 0;
		
		$.each(['#id_number', '#id_capacity', '#id_name'], function(k, v) {
			if($(v).val() === '')
			{
				nosend = 1;
			}
		});
		
		return nosend;
	}
});
