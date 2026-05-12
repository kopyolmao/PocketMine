<?php
/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/
namespace pocketmine\inventory;

use pocketmine\block\Planks;
use pocketmine\block\Quartz;
use pocketmine\block\Sandstone;
use pocketmine\block\Slab;
use pocketmine\block\Stone;
use pocketmine\block\StoneBricks;
use pocketmine\block\StoneWall;
use pocketmine\block\Wood;
use pocketmine\block\Wood2;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\utils\UUID;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Config;

class CraftingManager{
	/** @var Recipe[] */
	public $recipes = [];

	/** @var Recipe[][] */
	protected $recipeLookup = [];

	/** @var FurnaceRecipe[] */
	public $furnaceRecipes = [];

	/** @var BrewingRecipe[] */
	public $brewingRecipes = [];

	private static $RECIPE_COUNT = 0;

	public function __construct(bool $useJson = false){
		$this->registerBrewingStand();
		if($useJson){
			// load recipes from src/pocketmine/recipes.json
			$recipes = new Config(Server::getInstance()->getFilePath() . "src/pocketmine/resources/recipes.json", Config::JSON, []);

			MainLogger::getLogger()->info("Loading recipes...");
			foreach($recipes->getAll() as $recipe){
				switch($recipe["Type"]){
					case 0:
						// TODO: handle multiple result items
						if(count($recipe["Result"]) == 1){
							$first = $recipe["Result"][0];
							$result = new ShapelessRecipe(Item::get($first["ID"], $first["Damage"], $first["Count"]));

							foreach($recipe["Ingredients"] as $ingredient){
								$result->addIngredient(Item::get($ingredient["ID"], $ingredient["Damage"], $ingredient["Count"]));
							}
							$this->registerRecipe($result);
						}
						break;
					case 1:
						// TODO: handle multiple result items
						if(count($recipe["Result"]) == 1){
							$first = $recipe["Result"][0];
							$result = new ShapedRecipeFromJson(Item::get($first["ID"], $first["Damage"], $first["Count"]), $recipe["Height"], $recipe["Width"]);

							$shape = array_chunk($recipe["Ingredients"], $recipe["Width"]);
							foreach($shape as $y => $row){
								foreach($row as $x => $ingredient){
									$result->addIngredient($x, $y, Item::get($ingredient["ID"], ($ingredient["Damage"] < 0 ? null : $ingredient["Damage"]), $ingredient["Count"]));
								}
							}
							$this->registerRecipe($result);
						}
						break;
					case 2:
						$result = $recipe["Result"];
						$resultItem = Item::get($result["ID"], $result["Damage"], $result["Count"]);
						$this->registerRecipe(new FurnaceRecipe($resultItem, Item::get($recipe["Ingredients"], 0,1)));
						break;
					case 3:
						$result = $recipe["Result"];
						$resultItem = Item::get($result["ID"], $result["Damage"], $result["Count"]);
						$this->registerRecipe(new FurnaceRecipe($resultItem, Item::get($recipe["Ingredients"]["ID"], $recipe["Ingredients"]["Damage"], 1)));
						break;
					default:
						break;
				}
			}
		}else{
			//$this->registerStonecutter();
			$this->registerFurnace();
			$this->registerDyes();
			$this->registerIngots();
			$this->registerPotions();
			$this->registerTools();
			$this->registerWeapons();
			$this->registerArmor();
			$this->registerFood();
			$this->registerBrewingStand();
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::CLAY_BLOCK, 0, 1),
				"XX ",
				"XX ",
				"   "
			))->setIngredient("X", Item::get(Item::CLAY, 0, 4)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WORKBENCH, 0, 1),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::WOODEN_PLANK, null)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::GLOWSTONE_BLOCK, 0, 1),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::GLOWSTONE_DUST, 0, 4)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::LIT_PUMPKIN, 0, 1),
				"X ",
				"Y "
			))->setIngredient("X", Item::get(Item::PUMPKIN, 0, 1))->setIngredient("Y", Item::get(Item::TORCH, 0, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::SNOW_BLOCK, 0, 1),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::SNOWBALL)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::SNOW_LAYER, 0, 6),
				"X"
			))->setIngredient("X", Item::get(Item::SNOW_BLOCK, 0, 3)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::STICK, 0, 4),
				"X ",
				"X "
			))->setIngredient("X", Item::get(Item::WOODEN_PLANK, null)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::STONECUTTER, 0, 1),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::COBBLESTONE)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WOODEN_PLANK, Planks::OAK, 4),
				"X"
			))->setIngredient("X", Item::get(Item::WOOD, Wood::OAK, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WOODEN_PLANK, Planks::SPRUCE, 4),
				"X"
			))->setIngredient("X", Item::get(Item::WOOD, Wood::SPRUCE, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WOODEN_PLANK, Planks::BIRCH, 4),
				"X"
			))->setIngredient("X", Item::get(Item::WOOD, Wood::BIRCH, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WOODEN_PLANK, Planks::JUNGLE, 4),
				"X"
			))->setIngredient("X", Item::get(Item::WOOD, Wood::JUNGLE, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WOODEN_PLANK, Planks::ACACIA, 4),
				"X"
			))->setIngredient("X", Item::get(Item::WOOD2, Wood2::ACACIA, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WOODEN_PLANK, Planks::DARK_OAK, 4),
				"X"
			))->setIngredient("X", Item::get(Item::WOOD2, Wood2::DARK_OAK, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::WOOL, 0, 1),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::STRING, 0, 4)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::TORCH, 0, 4),
				"C ",
				"S"
			))->setIngredient("C", Item::get(Item::COAL, 0, 1))->setIngredient("S", Item::get(Item::STICK, 0, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::TORCH, 0, 4),
				"C ",
				"S"
			))->setIngredient("C", Item::get(Item::COAL, 1, 1))->setIngredient("S", Item::get(Item::STICK, 0, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::SUGAR, 0, 1),
				"S"
			))->setIngredient("S", Item::get(Item::SUGARCANE, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BED, 0, 1),
				"WWW",
				"PPP",
				"   "
			))->setIngredient("W", Item::get(Item::WOOL, null, 3))->setIngredient("P", Item::get(Item::WOODEN_PLANK, null, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::CHEST, 0, 1),
				"PPP",
				"P P",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, null, 8)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::ENCHANTMENT_TABLE, 0, 1),
				" B ",
				"DOD",
				"OOO"
			))->setIngredient("D", Item::get(Item::DIAMOND, 0, 2))->setIngredient("O", Item::get(Item::OBSIDIAN, 0, 4))->setIngredient("B", Item::get(Item::BOOK, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE, 0, 3),
				"PSP",
				"PSP",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 2))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::OAK, 4)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE, Planks::SPRUCE, 3),
				"PSP",
				"PSP",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 2))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::SPRUCE, 4)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE, Planks::BIRCH, 3),
				"PSP",
				"PSP",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 2))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::BIRCH, 4)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE, Planks::JUNGLE, 3),
				"PSP",
				"PSP",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 2))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::JUNGLE, 4)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE, Planks::ACACIA, 3),
				"PSP",
				"PSP",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 2))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::ACACIA, 4)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE, Planks::DARK_OAK, 3),
				"PSP",
				"PSP",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 2))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::DARK_OAK, 4)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE_GATE, 0, 1),
				"SPS",
				"SPS",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 4))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::OAK, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE_GATE_SPRUCE, 0, 1),
				"SPS",
				"SPS",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 4))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::SPRUCE, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE_GATE_BIRCH, 0, 1),
				"SPS",
				"SPS",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 4))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::BIRCH, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE_GATE_JUNGLE, 0, 1),
				"SPS",
				"SPS",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 4))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::JUNGLE, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE_GATE_DARK_OAK, 0, 1),
				"SPS",
				"SPS",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 4))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::DARK_OAK, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FENCE_GATE_ACACIA, 0, 1),
				"SPS",
				"SPS",
				"   "
			))->setIngredient("S", Item::get(Item::STICK, 0, 4))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::ACACIA, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FURNACE, 0, 1),
				"CCC",
				"C C",
				"CCC"
			))->setIngredient("C", Item::get(Item::COBBLESTONE, 0, 8)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::GLASS_PANE, 0, 16),
				"GGG",
				"GGG",
				"   "
			))->setIngredient("G", Item::get(Item::GLASS, 0, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::LADDER, 0, 2),
				"S S",
				"SSS",
				"S S"
			))->setIngredient("S", Item::get(Item::STICK, 0, 7)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::NETHER_REACTOR, 0, 1),
				"IDI",
				"IDI",
				"IDI"
			))->setIngredient("D", Item::get(Item::DIAMOND, 0, 3))->setIngredient("I", Item::get(Item::IRON_INGOT, 0, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::TRAPDOOR, 0, 2),
				"PPP",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, null, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOODEN_DOOR, 0, 3),
				"PP ",
				"PP ",
				"PP "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, null, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BIRCH_DOOR, 0, 3),
				"PP ",
				"PP ",
				"PP "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::BIRCH, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SPRUCE_DOOR, 0, 3),
				"PP ",
				"PP ",
				"PP "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::SPRUCE, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::JUNGLE_DOOR, 0, 3),
				"PP ",
				"PP ",
				"PP "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::JUNGLE, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::ACACIA_DOOR, 0, 3),
				"PP ",
				"PP ",
				"PP "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::ACACIA, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::DARK_OAK_DOOR, 0, 3),
				"PP ",
				"PP ",
				"PP "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::DARK_OAK, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::IRON_DOOR, 0, 1),
				"II ",
				"II ",
				"II "
			))->setIngredient("I", Item::get(Item::IRON_INGOT, 0, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOAT, 0, 1),
				"PSP",
				"PPP",
				"   "
			))->setIngredient("S", Item::get(Item::WOODEN_SHOVEL, 0, 1))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::OAK, 5)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOAT, 1, 1),
				"PSP",
				"PPP",
				"   "
			))->setIngredient("S", Item::get(Item::WOODEN_SHOVEL, 0, 1))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::SPRUCE, 5))); 
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOAT, 2, 1),
				"PSP",
				"PPP",
				"   "
			))->setIngredient("S", Item::get(Item::WOODEN_SHOVEL, 0, 1))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::BIRCH, 5))); 
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOAT, 3, 1),
				"PSP",
				"PPP",
				"   "
			))->setIngredient("S", Item::get(Item::WOODEN_SHOVEL, 0, 1))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::JUNGLE, 5)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOAT, 4, 1),
				"PSP",
				"PPP",
				"   "
			))->setIngredient("S", Item::get(Item::WOODEN_SHOVEL, 0, 1))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::ACACIA, 5)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOAT, 5, 1),
				"PSP",
				"PPP",
				"   "
			))->setIngredient("S", Item::get(Item::WOODEN_SHOVEL, 0, 1))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::DARK_OAK, 5)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOODEN_STAIRS, 0, 4),
				"  P",
				" PP",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::OAK, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOOD_SLAB, Planks::OAK, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::OAK, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SPRUCE_WOOD_STAIRS, 0, 4),
				"  P",
				" PP",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::SPRUCE, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOOD_SLAB, Planks::SPRUCE, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::SPRUCE, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BIRCH_WOOD_STAIRS, 0, 4),
				"  P",
				" PP",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::BIRCH, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOOD_SLAB, Planks::BIRCH, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::BIRCH, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::JUNGLE_WOOD_STAIRS, 0, 4),
				"P",
				"PP",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::JUNGLE, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOOD_SLAB, Planks::JUNGLE, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::JUNGLE, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::ACACIA_WOOD_STAIRS, 0, 4),
				"  P",
				" PP",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::ACACIA, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOOD_SLAB, Planks::ACACIA, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::ACACIA, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::DARK_OAK_WOOD_STAIRS, 0, 4),
				"  P",
				" PP",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::DARK_OAK, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOOD_SLAB, Planks::DARK_OAK, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, Planks::DARK_OAK, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BUCKET, 0, 1),
				"I I",
				" I ",
				"   "
			))->setIngredient("I", Item::get(Item::IRON_INGOT, 0, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::CLOCK, 0, 1),
				" G ",
				"GRG",
				" G "
			))->setIngredient("G", Item::get(Item::GOLD_INGOT, 0, 4))->setIngredient("R", Item::get(Item::REDSTONE_DUST, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::COMPASS, 0, 1),
				" I ",
				"IRI",
				" I"
			))->setIngredient("I", Item::get(Item::IRON_INGOT, 0, 4))->setIngredient("R", Item::get(Item::REDSTONE_DUST, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::TNT, 0, 1),
				"GSG",
				"SGS",
				"GSG"
			))->setIngredient("G", Item::get(Item::GUNPOWDER, 0, 5))->setIngredient("S", Item::get(Item::SAND, null, 4)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOWL, 0, 4),
				"P P",
				" P ",
				"   "
			))->setIngredient("P", Item::get(Item::WOODEN_PLANKS, null, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::MINECART, 0, 1),
				"I I",
				"III"
			))->setIngredient("I", Item::get(Item::IRON_INGOT, 0, 5)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOOK, 0, 1),
				"   ",
				"PP ",
				"P  "
			))->setIngredient("P", Item::get(Item::PAPER, 0, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BOOKSHELF, 0, 1),
				"PPP",
				"BBB",
				"PPP"
			))->setIngredient("P", Item::get(Item::WOODEN_PLANK, null, 6))->setIngredient("B", Item::get(Item::BOOK, 0, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::PAINTING, 0, 1),
				"SSS",
				"SWS",
				"SSS"
			))->setIngredient("S", Item::get(Item::STICK, 0, 8))->setIngredient("W", Item::get(Item::WOOL, null, 1)));
			$this->registerRecipe((new ShapedRecipe(Item::get(Item::PAPER, 0, 3),
				"   ",
				"SSS",
				"   "
			))->setIngredient("S", Item::get(Item::SUGARCANE, 0, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SIGN, 0, 3),
				"PPP",
				"PPP",
				" S"
			))->setIngredient("S", Item::get(Item::STICK, 0, 1))->setIngredient("P", Item::get(Item::WOODEN_PLANKS, null, 6))); //TODO: check if it gives one sign or three
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::IRON_BARS, 0, 16),
				"III",
				"III",
				"   "
			))->setIngredient("I", Item::get(Item::IRON_INGOT, 0, 6)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::GLASS_BOTTLE, 0, 3),
				"G G",
				" G ",
				"   "
			))->setIngredient("G", Item::get(Item::GLASS, 0, 3)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOODEN_PRESSURE_PLATE, 0, 1),
				"   ",
				"   ",
				"XX "
			))->setIngredient("X", Item::get(Item::WOODEN_PLANK, null, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::HEAVY_WEIGHTED_PRESSURE_PLATE, 0, 1),
				"   ",
				"XX ",
				"   "
			))->setIngredient("X", Item::get(Item::IRON_INGOT, 0, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::LIGHT_WEIGHTED_PRESSURE_PLATE, 0, 1),
				"   ",
				"XX ",
				"   "
			))->setIngredient("X", Item::get(Item::GOLD_INGOT, 0, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE_PRESSURE_PLATE, 0, 1),
				"   ",
				"XX ",
				"   "
			))->setIngredient("X", Item::get(Item::STONE, 0, 2)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::INACTIVE_REDSTONE_LAMP, 0, 1),
				" R ",
				"RGR",
				" R "
			))->setIngredient("R", Item::get(Item::REDSTONE, 0, 4))->setIngredient("G", Item::get(Item::GLOWSTONE_BLOCK, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::REDSTONE_TORCH, 0, 1),
				"   ",
				" R ",
				" S "
			))->setIngredient("R", Item::get(Item::REDSTONE, 0, 1))->setIngredient("S", Item::get(Item::STICK, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::WOODEN_BUTTON, 0, 1),
				"   ",
				" X ",
				"   "
			))->setIngredient("X", Item::get(Item::WOODEN_PLANK, null, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE_BUTTON, 0, 1),
				"   ",
				" X ",
				"   "
			))->setIngredient("X", Item::get(Item::COBBLESTONE, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::LEVER, 0, 1),
				"   ",
				" X ",
				" Y "
			))->setIngredient("X", Item::get(Item::STICK, 0, 1))->setIngredient("Y", Item::get(Item::COBBLESTONE, 0, 1)));
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::COBBLESTONE_STAIRS, 0, 4),
				"P  ",
				"PP ",
				"PPP"
			))->setIngredient("P", Item::get(Item::COBBLESTONE, 0, 6)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE_BRICK_STAIRS, 0, 4),
				"P  ",
				"PP ",
				"PPP"
			))->setIngredient("P", Item::get(Item::STONE_BRICK, 0, 6)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SLAB, 0, 6),
				"   ",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::STONE, '', 3)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SLAB, 5, 6),
				"   ",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::STONE_BRICK, '', 3)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE_BRICK, 3, 1),
				"   ",
				"PP "
			))->setIngredient("P", Item::get(Item::SLAB, 5, 2)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SLAB, 1, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::SANDSTONE, null, 3)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::RED_SANDSTONE_SLAB, 0, 6),
				"PPP",
				"   ",
				"   "
			))->setIngredient("P", Item::get(Item::RED_SANDSTONE, null, 3)));

			$this->registerRecipe((new ShapedRecipe(Item::get(Item::SANDSTONE, 1, 1),
				"P ",
				"P "
			))->setIngredient("P", Item::get(Item::SLAB, 1, 2)));

			$this->registerRecipe((new ShapedRecipe(Item::get(Item::RED_SANDSTONE, 1, 1),
				"P ",
				"P "
			))->setIngredient("P", Item::get(Item::RED_SANDSTONE_SLAB, 0, 2)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE_BRICK, 0, 4),
				"XX ",
				"XX "
			))->setIngredient("X", Item::get(Item::STONE, '', 4)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::QUARTZ_BLOCK, 0, 1),
				"XX ",
				"XX ",
				"   "
			))->setIngredient("X", Item::get(Item::QUARTZ, 0, 4)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BRICK_STAIRS, 0, 4),
				"P  ",
				"PP ",
				"PPP"
			))->setIngredient("P", Item::get(Item::BRICKS_BLOCK, 0, 6)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::BRICKS_BLOCK, 0, 1),
				"XX ",
				"XX "
			))->setIngredient("X", Item::get(Item::BRICK, 0, 4)));
			
			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FLOWER_POT, 0, 1),
				"X X",
				" X ",
				"   "
			))->setIngredient("X", Item::get(Item::BRICK, 0, 3)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SLAB, 4, 6),
				"   ",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::BRICKS_BLOCK, 0, 3)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::QUARTZ_BLOCK, 1, 1),
				"   ",
				"PP "
			))->setIngredient("P", Item::get(Item::SLAB, 6, 2)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SLAB, 3, 6),
				"   ",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::COBBLESTONE, 0, 3)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::COBBLESTONE, 0, 1),
				"   ",
				"PP "
			))->setIngredient("P", Item::get(Item::SLAB, 3, 2)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::COBBLESTONE_WALL, 0, 6),
				"PPP",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::COBBLESTONE, 0, 6)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::COBBLESTONE_WALL, 1, 6),
				"PPP",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::MOSS_STONE, 0, 6)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::NETHER_BRICKS, 0, 1),
				"XX ",
				"XX "
			))->setIngredient("X", Item::get(Item::NETHER_BRICK, 0, 4)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::NETHER_BRICKS_STAIRS, 0, 4),
				"XXX",
				"XXX"
			))->setIngredient("X", Item::get(Item::NETHER_BRICKS, 0, 6)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::QUARTZ_BLOCK, 2, 2),
				"P  ",
				"P  ",
				"   "
			))->setIngredient("P", Item::get(Item::QUARTZ_BLOCK, 0, 2)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SLAB, 6, 6),
				"   ",
				"PPP",
				"   "
			))->setIngredient("P", Item::get(Item::QUARTZ_BLOCK, 0, 3)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::SANDSTONE_STAIRS, 0, 4),
				"P  ",
				"PP ",
				"PPP"
			))->setIngredient("P", Item::get(Item::SANDSTONE, 0, 6)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::RED_SANDSTONE_STAIRS, 0, 4),
				"P  ",
				"PP ",
				"PPP"
			))->setIngredient("P", Item::get(Item::RED_SANDSTONE, 0, 6)));

			$this->registerRecipe((new ShapedRecipe(Item::get(Item::SANDSTONE, 0, 1),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::SAND, 0, 4)));

			$this->registerRecipe((new ShapedRecipe(Item::get(Item::RED_SANDSTONE, 0, 1),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::SAND, 1, 4)));

			$this->registerRecipe((new ShapedRecipe(Item::get(Item::SANDSTONE, 2, 4),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::SANDSTONE, 0, 4)));

			$this->registerRecipe((new ShapedRecipe(Item::get(Item::RED_SANDSTONE, 2, 4),
				"XX",
				"XX"
			))->setIngredient("X", Item::get(Item::RED_SANDSTONE, 0, 4)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE, Stone::POLISHED_GRANITE, 4),
				"XX ",
				"XX "
			))->setIngredient("X", Item::get(Item::STONE, Stone::GRANITE, 4)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE, Stone::POLISHED_DIORITE, 4),
				"XX ",
				"XX "
			))->setIngredient("X", Item::get(Item::STONE, Stone::DIORITE, 4)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE, Stone::POLISHED_ANDESITE, 4),
				"XX ",
				"XX "
			))->setIngredient("X", Item::get(Item::STONE, Stone::ANDESITE, 4)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE_BRICK, 1, 1),
				"   ",
				" Y ",
				" X "
			))->setIngredient("X", Item::get(Item::STONE_BRICK, 0, 1))->setIngredient("Y", Item::get(Item::VINES, 0, 1)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE, Stone::GRANITE, 1),
				"   ",
				" Y ",
				" X "
			))->setIngredient("X", Item::get(Item::STONE, Stone::DIORITE, 1))->setIngredient("Y", Item::get(Item::QUARTZ, 0, 1)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE, Stone::DIORITE, 2),
				"YY ",
				"XX ",
				"   "
			))->setIngredient("X", Item::get(Item::COBBLESTONE, 0, 2))->setIngredient("Y", Item::get(Item::QUARTZ, 0, 2)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::STONE, Stone::ANDESITE, 2),
				"   ",
				" Y ",
				" X "
			))->setIngredient("X", Item::get(Item::COBBLESTONE, 0, 1))->setIngredient("Y", Item::get(Item::STONE, Stone::DIORITE, 1)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::FLOWER_POT, 0, 1),
				"B B",
				" B ",
				"   "
			))->setIngredient("B", Item::get(Item::BRICK, null, 1)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::NOTEBLOCK, 0, 1),
				"PPP",
				"PRP",
				"PPP"
			))->setIngredient("P", Item::get(Item::PLANK, null, 1))->setIngredient("R", Item::get(Item::REDSTONE, null, 1)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::ITEM_FRAME, 0, 1),
				"SSS",
				"SLS",
				"SSS"
			))->setIngredient("S", Item::get(Item::STICK, null, 1))->setIngredient("L", Item::get(Item::LEATHER, null, 1)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::CAULDRON, 0, 1),
				"I I",
				"I I",
				"III"
			))->setIngredient("I", Item::get(Item::IRON_INGOT, null, 1)));

			$this->registerRecipe((new BigShapedRecipe(Item::get(Item::ANVIL, 0 ,1),
				"III",
				" G ",
				"GGG"
			))->setIngredient("I", Item::get(Item::IRON_BLOCK, 0, 1))->setIngredient("G", Item::get(Item::IRON_INGOT, null, 1)));
		}
	}

	protected function registerBrewingStand(){
		/*$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1))); //Akward
		// Potion
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1))); //Swiftness
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED_T, 1), Item::get(Item::REDSTONE, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1))); //Swiftness Extended
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1))); //Swiftness II
*/
		//Potion
		//WATER_BOTTLE
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE_EXTENDED, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
		//To WEAKNESS
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::MUNDANE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::THICK, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::MUNDANE_EXTENDED, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WEAKNESS, 1)));
		//GHAST_TEAR and BLAZE_POWDER
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRENGTH, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRENGTH_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRENGTH_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1)));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HEALING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFER_FISH, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HARMING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::HEALING_TWO, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::POISON_T, 1)));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE_T, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING_T, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SPEED_T, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SLOWNESS, 1)));
		//GOLDEN_CARROT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::INVISIBILITY, 1)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION_T, 1)));
