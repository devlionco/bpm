var array_courses = [];
	$(function(){
		$('#courses').multiSelect({
			selectableHeader: '<div class="custom-header">' + M.str.block_study_validation.choose_courses + '</div>',
			selectionHeader: '<div class="custom-header">' + M.str.block_study_validation.courses_selected + '</div>',	
		});
		
		$('#print').click(function () {
			if($('#courses').val() == null || $('#courses').val().length == 0)
			{
				alert(M.str.block_study_validation.not_selected_courses);
				return;
			}
			$.each($('#courses').val(), function(key, value) {
				$('#formcourses').append('<input type="text" name="courses[]" value="' + value + '" />');
			});
			$('#formcourses').submit();
			array_courses = [];
			$('#formcourses').children().remove();
			$('#courses').multiSelect('deselect_all');
			
		});
		
	});