<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $org    = Organization::where('slug', 'demo-org')->first();
        $editor = User::where('email', 'editor@examms.test')->first();

        if (! $org || ! $editor) {
            $this->command->warn('QuestionSeeder: demo-org or editor user not found. Skipping.');
            return;
        }

        // Define 5 realistic questions per category (16 categories total = 80 questions)
        $questionData = [
            'Science' => [
                [
                    'body' => '<p>Which planet is known as the Red Planet?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Mars', 'image_path' => null],
                        ['text' => 'Venus', 'image_path' => null],
                        ['text' => 'Earth', 'image_path' => null],
                        ['text' => 'Jupiter', 'image_path' => null]
                    ],
                    'correct_answer' => 'Mars',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Mars has iron oxide on its surface, giving it a reddish appearance.</p>'
                ],
                [
                    'body' => '<p>What is the chemical symbol for gold?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Au', 'image_path' => null],
                        ['text' => 'Ag', 'image_path' => null],
                        ['text' => 'Fe', 'image_path' => null],
                        ['text' => 'Cu', 'image_path' => null]
                    ],
                    'correct_answer' => 'Au',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>The symbol Au is derived from the Latin word aurum, meaning shining dawn.</p>'
                ],
                [
                    'body' => '<p>Which of the following are greenhouse gases?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Carbon dioxide', 'image_path' => null],
                        ['text' => 'Methane', 'image_path' => null],
                        ['text' => 'Oxygen', 'image_path' => null],
                        ['text' => 'Nitrogen', 'image_path' => null]
                    ],
                    'correct_answer' => 'Carbon dioxide',
                    'correct_answers' => ['Carbon dioxide', 'Methane'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 4],
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Carbon dioxide and methane trap heat in the atmosphere, causing greenhouse effects.</p>'
                ],
                [
                    'body' => '<p>Sound travels faster in water than in air.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Water is denser than air, which allows sound waves to travel about 4 times faster.</p>'
                ],
                [
                    'body' => '<p>Explain the process of photosynthesis.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Photosynthesis is the process by which green plants use sunlight to synthesize nutrients from carbon dioxide and water.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Chlorophyll traps solar energy to produce glucose and oxygen from CO2 and H2O.</p>'
                ]
            ],
            'Physics' => [
                [
                    'body' => '<p>What is the approximate speed of light in a vacuum?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '3x10^8 m/s', 'image_path' => null],
                        ['text' => '3x10^6 m/s', 'image_path' => null],
                        ['text' => '1.5x10^8 m/s', 'image_path' => null]
                    ],
                    'correct_answer' => '3x10^8 m/s',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Light travels at approximately 299,792 kilometers per second in a vacuum.</p>'
                ],
                [
                    'body' => '<p>Which of the following is a vector quantity?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Velocity', 'image_path' => null],
                        ['text' => 'Speed', 'image_path' => null],
                        ['text' => 'Mass', 'image_path' => null],
                        ['text' => 'Temperature', 'image_path' => null]
                    ],
                    'correct_answer' => 'Velocity',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Velocity has both magnitude and direction, making it a vector quantity.</p>'
                ],
                [
                    'body' => '<p>Select all fundamental forces of nature.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Gravitational Force', 'image_path' => null],
                        ['text' => 'Electromagnetic Force', 'image_path' => null],
                        ['text' => 'Weak Nuclear Force', 'image_path' => null],
                        ['text' => 'Frictional Force', 'image_path' => null]
                    ],
                    'correct_answer' => 'Gravitational Force',
                    'correct_answers' => ['Gravitational Force', 'Electromagnetic Force', 'Weak Nuclear Force'],
                    'marks_type' => 'multiple',
                    'marks_list' => [3, 5],
                    'marks' => 3,
                    'difficulty' => 'medium',
                    'explanation' => '<p>The four fundamental forces are gravity, electromagnetism, strong nuclear, and weak nuclear forces.</p>'
                ],
                [
                    'body' => '<p>Absolute zero is equal to -273.15 degrees Celsius.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>0 Kelvin is defined as absolute zero, which is exactly -273.15 °C.</p>'
                ],
                [
                    'body' => '<p>Define inertia according to Newton\'s first law of motion.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Inertia is the tendency of an object to resist any change in its state of rest or motion.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 4,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Objects remain in their state of motion unless acted upon by an external force.</p>'
                ]
            ],
            'Mechanics' => [
                [
                    'body' => '<p>What is the SI unit of force?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Newton', 'image_path' => null],
                        ['text' => 'Joule', 'image_path' => null],
                        ['text' => 'Watt', 'image_path' => null],
                        ['text' => 'Pascal', 'image_path' => null]
                    ],
                    'correct_answer' => 'Newton',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>The SI unit of force is the Newton (N), named after Isaac Newton.</p>'
                ],
                [
                    'body' => '<p>A car accelerates from rest at 2 m/s². How far does it travel in 5 seconds?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '25 meters', 'image_path' => null],
                        ['text' => '10 meters', 'image_path' => null],
                        ['text' => '50 meters', 'image_path' => null]
                    ],
                    'correct_answer' => '25 meters',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 3,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Using s = ut + 0.5at², s = 0 + 0.5 * 2 * 25 = 25 meters.</p>'
                ],
                [
                    'body' => '<p>Select all conservative forces.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Gravity', 'image_path' => null],
                        ['text' => 'Spring Force', 'image_path' => null],
                        ['text' => 'Friction', 'image_path' => null]
                    ],
                    'correct_answer' => 'Gravity',
                    'correct_answers' => ['Gravity', 'Spring Force'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 3],
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Work done by a conservative force is independent of the path taken.</p>'
                ],
                [
                    'body' => '<p>Work done by friction is always negative.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Friction forces act in the direction opposite to displacement, hence negative work.</p>'
                ],
                [
                    'body' => '<p>State the law of conservation of momentum.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>The total momentum of a closed system remains constant if no external forces act on it.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'hard',
                    'explanation' => '<p>Momentum before collision equals momentum after collision in isolated systems.</p>'
                ]
            ],
            'Optics' => [
                [
                    'body' => '<p>What phenomenon causes a rainbow?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Dispersion', 'image_path' => null],
                        ['text' => 'Polarization', 'image_path' => null],
                        ['text' => 'Interference', 'image_path' => null]
                    ],
                    'correct_answer' => 'Dispersion',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Light breaks into colors when passing through water droplets due to dispersion.</p>'
                ],
                [
                    'body' => '<p>A convex lens is also known as a:</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Converging lens', 'image_path' => null],
                        ['text' => 'Diverging lens', 'image_path' => null],
                        ['text' => 'Bifocal lens', 'image_path' => null]
                    ],
                    'correct_answer' => 'Converging lens',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>A convex lens bends light rays inward to a single focal point.</p>'
                ],
                [
                    'body' => '<p>Which of the following are primary colors of light?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Red', 'image_path' => null],
                        ['text' => 'Blue', 'image_path' => null],
                        ['text' => 'Green', 'image_path' => null],
                        ['text' => 'Yellow', 'image_path' => null]
                    ],
                    'correct_answer' => 'Red',
                    'correct_answers' => ['Red', 'Blue', 'Green'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 4],
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Red, Green, and Blue are primary colors in additive light synthesis.</p>'
                ],
                [
                    'body' => '<p>Light is an electromagnetic wave.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Light consists of oscillating electric and magnetic fields traveling through space.</p>'
                ],
                [
                    'body' => '<p>Explain total internal reflection.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Total internal reflection occurs when light travels from a denser to a rarer medium and the angle of incidence exceeds the critical angle.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'hard',
                    'explanation' => '<p>The light is completely reflected back into the denser medium.</p>'
                ]
            ],
            'Chemistry' => [
                [
                    'body' => '<p>What is the neutral pH value of pure water at 25°C?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '7', 'image_path' => null],
                        ['text' => '5', 'image_path' => null],
                        ['text' => '9', 'image_path' => null],
                        ['text' => '14', 'image_path' => null]
                    ],
                    'correct_answer' => '7',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Pure water has an equal concentration of H+ and OH- ions, yielding a pH of 7.</p>'
                ],
                [
                    'body' => '<p>Which element has the atomic number 1?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Hydrogen', 'image_path' => null],
                        ['text' => 'Helium', 'image_path' => null],
                        ['text' => 'Lithium', 'image_path' => null]
                    ],
                    'correct_answer' => 'Hydrogen',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Hydrogen is the simplest element with one proton and one electron.</p>'
                ],
                [
                    'body' => '<p>Which of the following are noble gases?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Helium', 'image_path' => null],
                        ['text' => 'Neon', 'image_path' => null],
                        ['text' => 'Nitrogen', 'image_path' => null],
                        ['text' => 'Oxygen', 'image_path' => null]
                    ],
                    'correct_answer' => 'Helium',
                    'correct_answers' => ['Helium', 'Neon'],
                    'marks_type' => 'multiple',
                    'marks_list' => [1, 2],
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Noble gases belong to Group 18 of the periodic table and are highly unreactive.</p>'
                ],
                [
                    'body' => '<p>An atom is larger than a molecule.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Molecules are composed of two or more atoms bonded together, hence they are larger.</p>'
                ],
                [
                    'body' => '<p>What is the difference between an exothermic and endothermic reaction?</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Exothermic reactions release energy/heat to the surroundings, whereas endothermic reactions absorb energy/heat.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 4,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Combustion is exothermic, while photosynthesis is endothermic.</p>'
                ]
            ],
            'Biology' => [
                [
                    'body' => '<p>What is the primary site of photosynthesis in plants?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Chloroplast', 'image_path' => null],
                        ['text' => 'Mitochondria', 'image_path' => null],
                        ['text' => 'Nucleus', 'image_path' => null]
                    ],
                    'correct_answer' => 'Chloroplast',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Chloroplasts contain chlorophyll which absorbs light energy for food synthesis.</p>'
                ],
                [
                    'body' => '<p>Which macromolecule encodes genetic information?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'DNA', 'image_path' => null],
                        ['text' => 'Protein', 'image_path' => null],
                        ['text' => 'Carbohydrate', 'image_path' => null]
                    ],
                    'correct_answer' => 'DNA',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Deoxyribonucleic acid (DNA) stores genetic codes in cells.</p>'
                ],
                [
                    'body' => '<p>Which of the following are eukaryotic organisms?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Fungi', 'image_path' => null],
                        ['text' => 'Plants', 'image_path' => null],
                        ['text' => 'Bacteria', 'image_path' => null]
                    ],
                    'correct_answer' => 'Fungi',
                    'correct_answers' => ['Fungi', 'Plants'],
                    'marks_type' => 'multiple',
                    'marks_list' => [3, 6],
                    'marks' => 3,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Bacteria are prokaryotes; fungi and plants contain membrane-bound organelles.</p>'
                ],
                [
                    'body' => '<p>All bacteria are harmful to humans.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Many bacteria are beneficial, assisting in digestion, food production, and ecology.</p>'
                ],
                [
                    'body' => '<p>Describe the function of red blood cells.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Red blood cells transport oxygen from the lungs to the rest of the body using hemoglobin.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Hemoglobin binds with oxygen molecules to transport them inside bloodstream.</p>'
                ]
            ],
            'Microbiology' => [
                [
                    'body' => '<p>Which scientist discovered penicillin?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Alexander Fleming', 'image_path' => null],
                        ['text' => 'Louis Pasteur', 'image_path' => null],
                        ['text' => 'Robert Koch', 'image_path' => null]
                    ],
                    'correct_answer' => 'Alexander Fleming',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Alexander Fleming discovered penicillin in 1928 when mold contaminated a Petri dish.</p>'
                ],
                [
                    'body' => '<p>What is the main component of bacterial cell walls?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Peptidoglycan', 'image_path' => null],
                        ['text' => 'Cellulose', 'image_path' => null],
                        ['text' => 'Chitin', 'image_path' => null]
                    ],
                    'correct_answer' => 'Peptidoglycan',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Peptidoglycan forms the mesh-like cell wall in bacteria.</p>'
                ],
                [
                    'body' => '<p>Select all diseases caused by viruses.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Influenza', 'image_path' => null],
                        ['text' => 'COVID-19', 'image_path' => null],
                        ['text' => 'Tuberculosis', 'image_path' => null]
                    ],
                    'correct_answer' => 'Influenza',
                    'correct_answers' => ['Influenza', 'COVID-19'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 4],
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Tuberculosis is caused by Mycobacterium tuberculosis, a bacterium.</p>'
                ],
                [
                    'body' => '<p>Viruses can replicate outside of a host cell.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Viruses lack cellular machinery and must hijack host cells to replicate.</p>'
                ],
                [
                    'body' => '<p>What is the gram stain method used for?</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>The gram stain method is used to classify bacteria into Gram-positive and Gram-negative based on cell wall composition.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'hard',
                    'explanation' => '<p>It helps doctors quickly identify bacterial types to prescribe antibiotics.</p>'
                ]
            ],
            'Genetics' => [
                [
                    'body' => '<p>Who is considered the father of genetics?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Gregor Mendel', 'image_path' => null],
                        ['text' => 'Charles Darwin', 'image_path' => null],
                        ['text' => 'Watson and Crick', 'image_path' => null]
                    ],
                    'correct_answer' => 'Gregor Mendel',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Mendel studied pea plants to discover laws of inheritance.</p>'
                ],
                [
                    'body' => '<p>How many chromosomes do normal human somatic cells contain?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '46', 'image_path' => null],
                        ['text' => '23', 'image_path' => null],
                        ['text' => '48', 'image_path' => null]
                    ],
                    'correct_answer' => '46',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Humans have 23 pairs of chromosomes, totaling 46.</p>'
                ],
                [
                    'body' => '<p>Which bases pair together in DNA?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Adenine and Thymine', 'image_path' => null],
                        ['text' => 'Cytosine and Guanine', 'image_path' => null],
                        ['text' => 'Adenine and Uracil', 'image_path' => null]
                    ],
                    'correct_answer' => 'Adenine and Thymine',
                    'correct_answers' => ['Adenine and Thymine', 'Cytosine and Guanine'],
                    'marks_type' => 'multiple',
                    'marks_list' => [1, 3],
                    'marks' => 1,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Uracil is only present in RNA, where it pairs with adenine.</p>'
                ],
                [
                    'body' => '<p>Phenotype refers to the genetic makeup of an organism.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Genotype is the genetic makeup; phenotype is the physical expression.</p>'
                ],
                [
                    'body' => '<p>Explain the central dogma of molecular biology.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>The central dogma explains that genetic information flows from DNA to RNA to Protein.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'medium',
                    'explanation' => '<p>It involves processes of transcription (DNA to RNA) and translation (RNA to protein).</p>'
                ]
            ],
            'Mathematics' => [
                [
                    'body' => '<p>What is the value of 5 factorial (5!)?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '120', 'image_path' => null],
                        ['text' => '24', 'image_path' => null],
                        ['text' => '60', 'image_path' => null]
                    ],
                    'correct_answer' => '120',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>5! = 5 * 4 * 3 * 2 * 1 = 120.</p>'
                ],
                [
                    'body' => '<p>What is the derivative of x² with respect to x?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '2x', 'image_path' => null],
                        ['text' => 'x', 'image_path' => null],
                        ['text' => '2', 'image_path' => null]
                    ],
                    'correct_answer' => '2x',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Applying power rule: d/dx(x^n) = n*x^(n-1).</p>'
                ],
                [
                    'body' => '<p>Select all prime numbers less than 10.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => '2', 'image_path' => null],
                        ['text' => '3', 'image_path' => null],
                        ['text' => '5', 'image_path' => null],
                        ['text' => '9', 'image_path' => null]
                    ],
                    'correct_answer' => '2',
                    'correct_answers' => ['2', '3', '5'],
                    'marks_type' => 'multiple',
                    'marks_list' => [1, 3, 5],
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>9 is divisible by 3, so it is composite; 2, 3, 5 are prime.</p>'
                ],
                [
                    'body' => '<p>A square is a type of rectangle.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>A rectangle is defined as a quadrilateral with four right angles. A square fits this definition.</p>'
                ],
                [
                    'body' => '<p>State the quadratic formula.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>x = (-b ± sqrt(b² - 4ac)) / (2a)</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Used to find roots of ax² + bx + c = 0.</p>'
                ]
            ],
            'Algebra' => [
                [
                    'body' => '<p>Solve for x: 3x - 7 = 14.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '7', 'image_path' => null],
                        ['text' => '5', 'image_path' => null],
                        ['text' => '6', 'image_path' => null]
                    ],
                    'correct_answer' => '7',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>3x = 21, so x = 7.</p>'
                ],
                [
                    'body' => '<p>What is the slope of the line y = 4x + 3?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '4', 'image_path' => null],
                        ['text' => '3', 'image_path' => null],
                        ['text' => '-4', 'image_path' => null]
                    ],
                    'correct_answer' => '4',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Applying slope-intercept form y = mx + c where m is the slope.</p>'
                ],
                [
                    'body' => '<p>Which of the following are factors of x² - 9?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'x - 3', 'image_path' => null],
                        ['text' => 'x + 3', 'image_path' => null],
                        ['text' => 'x - 9', 'image_path' => null]
                    ],
                    'correct_answer' => 'x - 3',
                    'correct_answers' => ['x - 3', 'x + 3'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 3],
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Using difference of squares: a² - b² = (a - b)(a + b).</p>'
                ],
                [
                    'body' => '<p>The product of two negative numbers is negative.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>A negative times a negative equals a positive.</p>'
                ],
                [
                    'body' => '<p>Explain what a matrix is.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>A matrix is a rectangular array of numbers arranged in rows and columns.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 4,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Commonly used in algebra to solve systems of linear equations.</p>'
                ]
            ],
            'Geometry' => [
                [
                    'body' => '<p>How many sides does a regular hexagon have?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '6', 'image_path' => null],
                        ['text' => '5', 'image_path' => null],
                        ['text' => '8', 'image_path' => null]
                    ],
                    'correct_answer' => '6',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>A hexagon has exactly 6 sides and 6 internal angles.</p>'
                ],
                [
                    'body' => '<p>What is the area of a circle with radius 7? (Use π = 22/7)</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '154', 'image_path' => null],
                        ['text' => '44', 'image_path' => null],
                        ['text' => '49', 'image_path' => null]
                    ],
                    'correct_answer' => '154',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Area = π * r² = (22/7) * 7 * 7 = 154.</p>'
                ],
                [
                    'body' => '<p>Select all acute angles.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => '30 degrees', 'image_path' => null],
                        ['text' => '45 degrees', 'image_path' => null],
                        ['text' => '90 degrees', 'image_path' => null]
                    ],
                    'correct_answer' => '30 degrees',
                    'correct_answers' => ['30 degrees', '45 degrees'],
                    'marks_type' => 'multiple',
                    'marks_list' => [1, 2],
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Acute angles are strictly less than 90 degrees.</p>'
                ],
                [
                    'body' => '<p>An equilateral triangle has three equal angles of 60 degrees.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Sum of angles is 180, and all 3 angles are equal, so 180/3 = 60 degrees.</p>'
                ],
                [
                    'body' => '<p>State the Pythagorean theorem.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>a² + b² = c²</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 4,
                    'difficulty' => 'easy',
                    'explanation' => '<p>In right triangles, the square of the hypotenuse is equal to the sum of the squares of the other two sides.</p>'
                ]
            ],
            'Computer Science' => [
                [
                    'body' => '<p>Which data structure operates on a Last In First Out (LIFO) basis?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Stack', 'image_path' => null],
                        ['text' => 'Queue', 'image_path' => null],
                        ['text' => 'Array', 'image_path' => null]
                    ],
                    'correct_answer' => 'Stack',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Stacks insert and remove elements from the same end, yielding LIFO behavior.</p>'
                ],
                [
                    'body' => '<p>What does CPU stand for?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Central Processing Unit', 'image_path' => null],
                        ['text' => 'Computer Personal Unit', 'image_path' => null],
                        ['text' => 'Central Peripheral Utility', 'image_path' => null]
                    ],
                    'correct_answer' => 'Central Processing Unit',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>CPU acts as the primary brain/microprocessor of a computer system.</p>'
                ],
                [
                    'body' => '<p>Select all high-level programming languages.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Python', 'image_path' => null],
                        ['text' => 'Java', 'image_path' => null],
                        ['text' => 'Assembly', 'image_path' => null]
                    ],
                    'correct_answer' => 'Python',
                    'correct_answers' => ['Python', 'Java'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 4],
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Assembly is low-level, translating directly to machine instructions.</p>'
                ],
                [
                    'body' => '<p>HTTP is a secure protocol by default.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>HTTPS is the secure version of HTTP, utilizing SSL/TLS for encryption.</p>'
                ],
                [
                    'body' => '<p>Define what an algorithm is.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>An algorithm is a step-by-step set of instructions for solving a problem or performing a task.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 4,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Algorithms must be finite, clear, and produce an output.</p>'
                ]
            ],
            'Programming' => [
                [
                    'body' => '<p>Which keyword is used to declare block-scoped variables in modern JavaScript?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'let', 'image_path' => null],
                        ['text' => 'var', 'image_path' => null],
                        ['text' => 'constant', 'image_path' => null]
                    ],
                    'correct_answer' => 'let',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Both let and const are block-scoped; var is function-scoped.</p>'
                ],
                [
                    'body' => '<p>What is the output of print(10 // 3) in Python?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => '3', 'image_path' => null],
                        ['text' => '3.333', 'image_path' => null],
                        ['text' => '1', 'image_path' => null]
                    ],
                    'correct_answer' => '3',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>The // operator is floor division, dropping the fractional part.</p>'
                ],
                [
                    'body' => '<p>Select all Object Oriented Programming (OOP) concepts.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Inheritance', 'image_path' => null],
                        ['text' => 'Encapsulation', 'image_path' => null],
                        ['text' => 'Recursion', 'image_path' => null]
                    ],
                    'correct_answer' => 'Inheritance',
                    'correct_answers' => ['Inheritance', 'Encapsulation'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 4],
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Recursion is a programming technique, not a fundamental OOP concept.</p>'
                ],
                [
                    'body' => '<p>In recursion, a function calls itself.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Recursive functions solve problems by dividing them into sub-problems solved by self-calls.</p>'
                ],
                [
                    'body' => '<p>What is the purpose of version control systems like Git?</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Git tracks changes in source code during software development and allows multiple developers to collaborate.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 4,
                    'difficulty' => 'easy',
                    'explanation' => '<p>It maintains history, branches, and merges edits safely.</p>'
                ]
            ],
            'Frontend Development' => [
                [
                    'body' => '<p>What HTML tag is used to display an image?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'img', 'image_path' => null],
                        ['text' => 'image', 'image_path' => null],
                        ['text' => 'pic', 'image_path' => null]
                    ],
                    'correct_answer' => 'img',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>The &lt;img&gt; tag is a self-closing element used to embed images.</p>'
                ],
                [
                    'body' => '<p>Which CSS property changes text color?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'color', 'image_path' => null],
                        ['text' => 'text-color', 'image_path' => null],
                        ['text' => 'font-color', 'image_path' => null]
                    ],
                    'correct_answer' => 'color',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>The color property changes foreground color of text in CSS.</p>'
                ],
                [
                    'body' => '<p>Which of the following are JavaScript libraries or frameworks?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'React', 'image_path' => null],
                        ['text' => 'Vue', 'image_path' => null],
                        ['text' => 'Laravel', 'image_path' => null]
                    ],
                    'correct_answer' => 'React',
                    'correct_answers' => ['React', 'Vue'],
                    'marks_type' => 'multiple',
                    'marks_list' => [2, 3],
                    'marks' => 2,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Laravel is a PHP backend framework; React and Vue are frontend JS technologies.</p>'
                ],
                [
                    'body' => '<p>CSS stands for Cascading Style Sheets.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'True',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>CSS styles markup documents (like HTML) dynamically.</p>'
                ],
                [
                    'body' => '<p>Explain the difference between absolute and relative positioning in CSS.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Absolute positioning places an element relative to its nearest positioned ancestor, whereas relative positioning places it relative to its normal position in the document flow.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'medium',
                    'explanation' => '<p>Relative elements preserve space in the layout; absolute elements do not.</p>'
                ]
            ],
            'Backend Development' => [
                [
                    'body' => '<p>Which protocol is primarily used for WebSockets?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'ws', 'image_path' => null],
                        ['text' => 'http', 'image_path' => null],
                        ['text' => 'ftp', 'image_path' => null]
                    ],
                    'correct_answer' => 'ws',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 2,
                    'difficulty' => 'medium',
                    'explanation' => '<p>ws (WebSocket) or wss (Secure) is the protocol for full-duplex WebSocket connections.</p>'
                ],
                [
                    'body' => '<p>What PHP framework is this application built on?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Laravel', 'image_path' => null],
                        ['text' => 'Symfony', 'image_path' => null],
                        ['text' => 'CodeIgniter', 'image_path' => null]
                    ],
                    'correct_answer' => 'Laravel',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Laravel is a highly expressive, elegant MVC framework for PHP.</p>'
                ],
                [
                    'body' => '<p>Select all relational database engines.</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'MySQL', 'image_path' => null],
                        ['text' => 'PostgreSQL', 'image_path' => null],
                        ['text' => 'MongoDB', 'image_path' => null]
                    ],
                    'correct_answer' => 'MySQL',
                    'correct_answers' => ['MySQL', 'PostgreSQL'],
                    'marks_type' => 'multiple',
                    'marks_list' => [1, 2, 3],
                    'marks' => 1,
                    'difficulty' => 'medium',
                    'explanation' => '<p>MongoDB is a document-oriented NoSQL database system.</p>'
                ],
                [
                    'body' => '<p>REST APIs communicate exclusively using XML format.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>JSON is the most common format for REST API communication, though XML is also supported.</p>'
                ],
                [
                    'body' => '<p>Explain the purpose of middleware in web frameworks.</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Middleware inspects and filters HTTP requests entering the application, commonly handling authentication or CORS.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 4,
                    'difficulty' => 'medium',
                    'explanation' => '<p>It serves as a bridge/pipeline between incoming request and final route handler.</p>'
                ]
            ],
            'Database Management' => [
                [
                    'body' => '<p>What command retrieves data from a SQL database?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'SELECT', 'image_path' => null],
                        ['text' => 'GET', 'image_path' => null],
                        ['text' => 'RETRIEVE', 'image_path' => null]
                    ],
                    'correct_answer' => 'SELECT',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>SELECT query returns table rows based on criteria.</p>'
                ],
                [
                    'body' => '<p>What does SQL stand for?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => false,
                    'options' => [
                        ['text' => 'Structured Query Language', 'image_path' => null],
                        ['text' => 'Standard Query List', 'image_path' => null],
                        ['text' => 'Simple Query Language', 'image_path' => null]
                    ],
                    'correct_answer' => 'Structured Query Language',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>SQL is standard language for managing relational databases.</p>'
                ],
                [
                    'body' => '<p>Which of the following are NoSQL database categories?</p>',
                    'type' => 'mcq',
                    'allows_multiple' => true,
                    'options' => [
                        ['text' => 'Document Store', 'image_path' => null],
                        ['text' => 'Key-Value Store', 'image_path' => null],
                        ['text' => 'Relational Store', 'image_path' => null]
                    ],
                    'correct_answer' => 'Document Store',
                    'correct_answers' => ['Document Store', 'Key-Value Store'],
                    'marks_type' => 'multiple',
                    'marks_list' => [3, 5],
                    'marks' => 3,
                    'difficulty' => 'medium',
                    'explanation' => '<p>NoSQL covers document, key-value, column, and graph databases.</p>'
                ],
                [
                    'body' => '<p>A primary key can contain null values.</p>',
                    'type' => 'true_false',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => 'False',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 1,
                    'difficulty' => 'easy',
                    'explanation' => '<p>Primary keys must uniquely identify rows and cannot contain NULL.</p>'
                ],
                [
                    'body' => '<p>What is database normalization?</p>',
                    'type' => 'short_answer',
                    'allows_multiple' => false,
                    'options' => null,
                    'correct_answer' => '<p>Database normalization is the process of structuring a database to reduce data redundancy and improve data integrity.</p>',
                    'correct_answers' => null,
                    'marks_type' => 'single',
                    'marks_list' => null,
                    'marks' => 5,
                    'difficulty' => 'hard',
                    'explanation' => '<p>It involves organizing columns and tables to satisfy normal forms.</p>'
                ]
            ]
        ];

        // Loop through the definitions and seed
        foreach ($questionData as $catName => $questions) {
            $cat = QuestionCategory::where('organization_id', $org->id)
                ->where('name', $catName)
                ->first();

            if (! $cat) {
                $this->command->warn("QuestionSeeder: Category '{$catName}' not found. Skipping its questions.");
                continue;
            }

            foreach ($questions as $q) {
                Question::updateOrCreate(
                    [
                        'organization_id' => $org->id,
                        'body'            => $q['body'],
                    ],
                    array_merge($q, [
                        'organization_id' => $org->id,
                        'category_id'    => $cat->id,
                        'created_by'     => $editor->id,
                        'status'         => 'active',
                        'meta_title'     => $cat->name . ' Question',
                        'slug'           => \Illuminate\Support\Str::slug(strip_tags($q['body'])),
                        'ai_generated'   => (bool) rand(0, 1),
                        'ai_improve'     => (bool) rand(0, 1),
                    ])
                );
            }
        }

        $this->command->info('✓ QuestionSeeder: 80 realistic questions seeded successfully (5 per category).');
    }
}
