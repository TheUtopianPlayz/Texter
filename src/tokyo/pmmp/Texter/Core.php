<?php

/**
 * // English
 *
 * Texter, the display FloatingTextPerticle plugin for PocketMine-MP
 * Copyright (c) 2018 yuko fuyutsuki < https://github.com/fuyutsuki >
 *
 * This software is distributed under "MIT license".
 * You should have received a copy of the MIT license
 * along with this program.  If not, see
 * < https://opensource.org/licenses/mit-license >.
 *
 * ---------------------------------------------------------------------
 * // 日本語
 *
 * TexterはPocketMine-MP向けのFloatingTextPerticleを表示するプラグインです。
 * Copyright (c) 2018 yuko fuyutsuki < https://github.com/fuyutsuki >
 *
 * このソフトウェアは"MITライセンス"下で配布されています。
 * あなたはこのプログラムと共にMITライセンスのコピーを受け取ったはずです。
 * 受け取っていない場合、下記のURLからご覧ください。
 * < https://opensource.org/licenses/mit-license >
 */

namespace tokyo\pmmp\Texter;

// pocketmine
use pocketmine\{
  plugin\PluginBase,
  lang\BaseLang,
  utils\TextFormat as TF
};

// texter
use tokyo\pmmp\Texter\{
  manager\ConfigDataManager,
  manager\CrftsDataManager,
  manager\FtsDataManager,
  manager\Manager,
  task\CheckUpdateTask,
  task\PrepareTextsTask
};

// NOTE mcbeformapi
//use tokyo\pmmp\MCBEFormAPI\{
//  test
//};

/**
 * TexterCore
 */
class Core extends PluginBase {

  private const LANG_DIR = "language";
  private const LANG_FALLBACK = "eng";

  public const CODENAME = "Phyllorhiza punctata";
  public const DS = DIRECTORY_SEPARATOR;

  /** @var string */
  public $dir = "";
  /** @var ?ConfigDataManager */
  private $configDm = null;
  /** @var ?CrftsDataManager */
  private $crftsDm = null;
  /** @var ?FtsDataManager */
  private $ftsDm = null;
  /** @var ?TexterApi */
  private $api = null;
  /** @var ?BaseLang */
  private $lang = null;

  /**
   * @return ?ConfigDataManager
   */
  public function getConfigDataManager(): ?ConfigDataManager {
    return $this->configDm;
  }

  /**
   * @return ?CrftsDataManager
   */
  public function getCrftsDataManager(): ?CrftsDataManager {
    return $this->crftsDm;
  }

  /**
   * @return ?FtsDataManager
   */
  public function getFtsDataManager(): ?FtsDataManager {
    return $this->ftsDm;
  }

  /**
   * @return ?TexterApi
   */
  public function getApi(): ?TexterApi {
    return $this->api;
  }

  /**
   * @return ?BaseLang
   */
  public function getLang(): ?BaseLang {
    return $this->lang;
  }

  public function onLoad() {
    $this->dir = $this->getDataFolder();
    $this->initDataManagers();
    $this->initApi();
    $this->initLang();
    $this->registerCommands();
    $this->checkUpdate();
    $this->setTimezone();
  }

  public function onEnable() {
    $this->prepareTexts();
    $listener = new EventListener($this);
    $this->getServer()->getPluginManager()->registerEvents($listener, $this);
  }

  private function initDataManagers(): void {
    $this->configDm = new ConfigDataManager($this);
    $this->crftsDm = new CrftsDataManager($this);
    $this->ftsDm = new FtsDataManager($this);
  }

  private function initApi(): void {
    $this->api = new TexterApi($this);
  }

  private function initLang(): void {
    $langCode = $this->configDm->getLangCode();
    $this->saveResource(self::LANG_DIR.self::DS."eng.ini");
    $this->saveResource(self::LANG_DIR.self::DS.$langCode.".ini");
    $this->lang = new BaseLang($langCode, $this->dir.self::LANG_DIR.self::DS, self::LANG_FALLBACK);
    $message = $this->lang->translateString("language.selected", [
      $this->lang->translateString("language.name"),
      $langCode
    ]);
    $this->getLogger()->info(TF::GREEN.$message);
  }

  private function registerCommands(): void {
    if ($this->configDm->getCanUseCommands()) {
      $map = $this->getServer()->getCommandMap();
      $commands = [
        // TODO:
        // new TxtCommand($this),
        // new TxtAdmCommand($this)
      ];
      $map->registerAll($this->getName(), $commands);
      $message = $this->lang->translateString("on.load.commands.on");
      $this->getLogger()->info(TF::GREEN.$message);
    }else {
      $message = $this->lang->translateString("on.load.commands.off");
      $this->getLogger()->info(TF::RED.$message);
    }
  }

  private function checkUpdate(): void {
    if ($this->configDm->getCheckUpdate()) {
      try {
        $task = new CheckUpdateTask();
        $this->getServer()->getScheduler()->scheduleAsyncTask($task);
      } catch (\Exception $e) {
        $this->getLogger()->warning($e->getMessage());
      }
    }
    if (strpos($this->getDescription()->getVersion(), "-") !== false) {
      $this->getLogger()->notice($this->lang->translateString("version.dev"));
    }
  }

  public function versionCompare(string $newVer, string $url): void {
    $curVer = $this->getDescription()->getVersion();
    if (version_compare($newVer, $curVer, "=")) {
      $message = $this->lang->translateString("on.load.update.nothing");
      $this->getLogger()->notice($message);
    }elseif (version_compare($newVer, $curVer, ">")) {
      $message1 = $this->lang->translateString("on.load.update.available.1", [
        $newVer,
        $curVer
      ]);
      $message2 = $this->lang->translateString("on.load.update.available.2");
      $message3 = $this->lang->translateString("on.load.update.available.3", [
        $url
      ]);
      $this->getLogger()->notice($message1);
      $this->getLogger()->notice($message2);
      $this->getLogger()->notice($message3);
    }
  }

  private function setTimezone(): void {
    $timezone = $this->configDm->getTimezone();
    if ($timezone !== "") {
      date_default_timezone_set($timezone);
      $message = $this->lang->translateString("on.load.timezone", [
        $timezone
      ]);
      $this->getLogger()->info(TF::GREEN.$message);
    }
  }

  private function prepareTexts(): void {
    $task = new PrepareTextsTask($this);
    $this->getServer()->getScheduler()->scheduleRepeatingTask($task, 1);
  }
}
