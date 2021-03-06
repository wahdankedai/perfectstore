<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\FormGroup;
use App\FormType;
use App\Form;
use App\SingleSelect;
use App\FormSingleSelect;
use App\MultiSelect;
use App\FormMultiSelect;
use App\AuditTemplate;
use App\AuditTemplateForm;
use App\FormCategory;
use App\FormFormula;
use App\User;
use App\UserMapping;

use App\AuditTemplateCategory;
use App\AuditTemplateGroup;
use App\FormRepository;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;


class ImportAuditTemplateTableSeeder extends Seeder
{
	public function run()
	{
		Model::unguard();

		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		DB::table('form_categories')->truncate();
		DB::table('form_groups')->truncate();
		DB::table('forms')->truncate();

		DB::table('single_selects')->truncate();
		DB::table('form_single_selects')->truncate();
		DB::table('multi_selects')->truncate();
		DB::table('form_multi_selects')->truncate();

		DB::table('form_formulas')->truncate();
		DB::table('form_conditions')->truncate();

		DB::table('audit_template_forms')->truncate();
		DB::table('audit_template_groups')->truncate();
		DB::table('audit_template_categories')->truncate();


		// \DB::beginTransaction();

        // try {
            $folderpath = 'database/seeds/seed_files';
			$folders = File::directories($folderpath);
			$latest = '11232015';
			foreach ($folders as $value) {
				$_dir = explode("/", $value);
				$cnt = count($_dir);
				$name = $_dir[$cnt - 1];
				$latest_date = DateTime::createFromFormat('mdY', $latest);
				$now = DateTime::createFromFormat('mdY', $name);
				if($now > $latest_date){
					$latest = $name;
				}
			}
			// $this->filename = $folderpath.$latest.'/mtprimarysales.csv';
			$files = File::allFiles($folderpath."/".$latest."/templates");

			foreach ($files as $file)
			{
			    echo (string)$file, "\n";
			    $reader = ReaderFactory::create(Type::XLSX); // for XLSX files
				$reader->open($file);

				//   // Accessing the sheet name when reading
				foreach ($reader->getSheetIterator() as $sheet) {
					if($sheet->getName() == 'Sheet1'){
						$cnt = 0;
						foreach ($sheet->getRowIterator() as $row) {
							if($cnt > 0){
								if($row[1] != ''){	
									$start_date = $row[4];
									$end_date = $row[5];

									$template = AuditTemplate::firstOrCreate(['template' => $row[1]]);
									$template->start_date = $start_date;
									$template->end_date = $end_date;
									$template->update();
									
									$category = FormCategory::firstOrCreate(['category' => $row[3]]);

									// dd($category);

									$group = FormGroup::firstOrCreate(['group_desc' => $row[6]]);

									$type = $row[11];

									if(strtoupper($type) == 'DOUBLE'){
										$form_type = FormType::where('form_type', "NUMERIC")->first();
									}else{
										$form_type = FormType::where('form_type', strtoupper($type))->first();
									}

									$image = "";
									if(!empty($row[15])){
										$image = $row[15];
									}

									if($form_type->id == 11){
										$index1 = array();
										$index2 = array();
										preg_match_all('/{(.*?)}/', $row[12], $matches);
										foreach ($matches[1] as $key => $a ){
											
											$data = DB::table('temp_forms')->where('code',$a)->first();
											if(!empty($data)){
												$other_form = FormRepository::insertForm(
													$template,
													$a,
													$data->type,
													$data->required,
													$data->prompt,
													$data->choices,
													$data->expected_answer,
													$image,
													null,
													null,
													null);
												$index1[$a] = $other_form->id;
												$index2[$a] = $other_form->prompt.'_'.$other_form->id;
											}else{
												$other_form = DB::table('forms')->where('code',$a)->first();
												// echo $a;
												if(empty($other_form)){
													// dd($a);
												}
												
												// $other_form = FormRepository::insertForm($template,$a,$data->form_type_id,$data->required,$data->prompt,null,$data->expected_answer,null,$image);
												
												$index1[$a] = '!'.$other_form->id;
												$index2[$a] = $other_form->prompt.'_'.$other_form->id;

											}

											
											
										}
										$formula1 = $row[12];
										$formula2 = $row[12];
										foreach ($matches[1] as $key => $a ){
											$formula1 = str_replace('{'.$a.'}',$index1[$a], $formula1);
											$formula2 = str_replace('{'.$a.'}', ' :'.$index2[$a].': ', $formula2);
											
										}
										$form = FormRepository::insertForm(
											$template,
											$row[7],
											$row[11],
											$row[10],
											$row[9],
											$formula1,
											null,
											$image,
											$formula2,
											null,
											null);
										
									}elseif ($form_type->id == 12) {
										$options = explode("~", $row[12]);


										preg_match_all('/(.*?){(.*?)}/', $row[12], $matches);
										$data_con = array();
										$options = explode("~", $row[12]);


										foreach ($options as $option) {
											$with_value = preg_match('/{(.*?)}/', $option, $match);
											$x1 = array();
											$x2 = array();
											$_opt1 = "";
											$_opt2 = "";
											if($with_value){
												$codes = explode('^', $match[1]);
												
												if(count($codes)> 0){
													foreach ($codes as $code) {
														if($code != ''){
															$other_data = DB::table('temp_forms')->where('code',$code)->first();

															if(empty($other_data)){
																$other_data = DB::table('forms')->where('code',$code)->first();
															}

															$other_form = FormRepository::insertForm(
																$template,
																$code,
																$other_data->type,
																$other_data->required,
																$other_data->prompt,
																$other_data->choices,
																$other_data->expected_answer,
																null,
																null,
																null,
																null);

															
															$x1[] = $other_form->id;
															$x2[] = $other_form->prompt.'_'.$other_form->id;
														}
														
														
													}
													
												}

												
												
												if(count($x1) > 0){
													$_opt1 = implode("^", $x1);
												}
												if(count($x2) > 0){
													$_opt2 = implode("^", $x2);
												}
											}
											
											$data_con[] = ['option' => strtoupper(strtok($option, '{')), 'condition' => $_opt1, 'condition_desc' => $_opt2];
											
										}
										// dd($data_con);
										$form = FormRepository::insertForm(
											$template,
											$row[7],
											$row[11],
											$row[10],
											$row[9],
											$row[12],
											$row[14],
											$image,
											array(),
											$data_con,
											$row[16]);
										
									}else{

										$form = FormRepository::insertForm(
											$template,
											$row[7],
											$row[11],
											$row[10],
											$row[9],
											$row[12],
											$row[14],
											$image,
											null,
											null,
											$row[16]);

									}


									$cat_order = 1;
								$a_cat_id = 0;
								$clast_cnt = AuditTemplateCategory::getLastOrder($template->id);
								if(empty($clast_cnt)){
									$a_cat = AuditTemplateCategory::create(['category_order' => $cat_order,
										'audit_template_id' => $template->id, 
										'category_id' => $category->id]);
									$a_cat_id = $a_cat->id;
								}else{
									$cat = AuditTemplateCategory::categoryExist($template->id, $category->id);
									if(empty($cat)){
										$cat_order = $clast_cnt->category_order + 1;
										$a_cat = AuditTemplateCategory::create(['category_order' => $cat_order,
											'audit_template_id' => $template->id, 
											'category_id' => $category->id]);
										$a_cat_id = $a_cat->id;
									}else{
										$a_cat_id = $cat->id;
									}
								}

								$grp_order = 1;
								$a_grp_id = 0;
								$glast_cnt = AuditTemplateGroup::getLastOrder($a_cat_id);
								if(empty($glast_cnt)){
									$a_grp = AuditTemplateGroup::create(['group_order' => $grp_order,
										'audit_template_category_id' => $a_cat_id, 
										'form_group_id' => $group->id]);
									$a_grp_id = $a_grp->id;
								}else{
									$grp = AuditTemplateGroup::categoryExist($a_cat_id, $group->id);
									if(empty($grp)){
										$grp_order = $glast_cnt->group_order + 1;
										$a_grp = AuditTemplateGroup::create(['group_order' => $grp_order,
											'audit_template_category_id' => $a_cat_id, 
											'form_group_id' => $group->id]);
										$a_grp_id = $a_grp->id;
									}else{
										$a_grp_id = $grp->id;
									}
								}


								$order = 1;
								$a_frm_id = 0;
								$last_cnt = AuditTemplateForm::getLastOrder($a_grp_id);
								if(!empty($last_cnt)){
									$order = $last_cnt->order + 1;
								}


								AuditTemplateForm::create(array(
									'audit_template_group_id' => $a_grp_id,
									'order' => $order,
									'audit_template_id' => $template->id, 
									'form_id' => $form->id
									));
								}


								
							}
							$cnt++;
						}
					}
					// }elseif($sheet->getName() == 'Others') {
					// 	// $cnt = 0;
					// 	// foreach ($sheet->getRowIterator() as $row) {
					// 	// 	if($cnt > 0){
					// 	// 		if(!is_null($row[2])){
					// 	// 			$prompt = addslashes($row[1]);
					// 	// 			DB::statement('INSERT INTO temp_forms (code, prompt, required, type, choices) VALUES ("'.$row[0].'","'.$prompt.'","'.$row[2].'","'.$row[3].'","'.$row[4].'");');
					// 	// 		}
					// 	// 	}
					// 	// 	$cnt++;
					  
					// 	// }
					// }else{

					// }
				}

				$reader->close();
			}

			$users = User::all();
			foreach ($users as $user) {
				$mapping = UserMapping::where('user_name', $user->name)
					->where('start_date', $start_date)
					->where('end_date', $end_date)
					->first();
				if(!empty($mapping)){
					$mapping->mapped_stores = $user->stores->count();
					$mapping->update();
				}else{
					UserMapping::create(['user_name' =>$user->name,
						'start_date' => $start_date,
						'end_date' => $end_date,
						'mapped_stores' => $user->stores->count()]);

				}
			}


		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
		Model::reguard();
	}
}
