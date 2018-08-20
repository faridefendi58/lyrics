<?php
namespace Extensions;

class SongService
{
    protected $basePath;
    protected $themeName;
    protected $adminPath;
    protected $tablePrefix;

    public function __construct($settings = null)
    {
        $this->basePath = (is_object($settings))? $settings['basePath'] : $settings['settings']['basePath'];
        $this->themeName = (is_object($settings))? $settings['theme']['name'] : $settings['settings']['theme']['name'];
        $this->adminPath = (is_object($settings))? $settings['admin']['path'] : $settings['settings']['admin']['path'];
        $this->tablePrefix = (is_object($settings))? $settings['db']['tablePrefix'] : $settings['settings']['db']['tablePrefix'];
    }
    
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_song` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(128) DEFAULT NULL,
          `slug` varchar(128) DEFAULT NULL,
          `artist_id` int(11) DEFAULT '0',
          `genre_id` int(11) DEFAULT '0',
          `status` varchar(16) DEFAULT 'draft' COMMENT 'draft, published, archived',
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sql .= "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_song_abjads` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(16) DEFAULT NULL,
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;";

        $sql .= "INSERT INTO `{tablePrefix}ext_song_abjads` (`id`, `title`, `created_at`, `updated_at`) VALUES
            (1, 'A', '{created_at}'), '{updated_at}'),
            (2, 'B', '{created_at}'), '{updated_at}'),
            (3, 'C', '{created_at}'), '{updated_at}'),
            (4, 'D', '{created_at}'), '{updated_at}'),
            (5, 'E', '{created_at}'), '{updated_at}'),
            (6, 'F', '{created_at}'), '{updated_at}'),
            (7, 'G', '{created_at}'), '{updated_at}'),
            (8, 'H', '{created_at}'), '{updated_at}'),
            (9, 'I', '{created_at}'), '{updated_at}'),
            (10, 'J', '{created_at}'), '{updated_at}'),
            (11, 'K', '{created_at}'), '{updated_at}'),
            (12, 'L', '{created_at}'), '{updated_at}'),
            (13, 'M', '{created_at}'), '{updated_at}'),
            (14, 'N', '{created_at}'), '{updated_at}'),
            (15, 'O', '{created_at}'), '{updated_at}'),
            (16, 'P', '{created_at}'), '{updated_at}'),
            (17, 'Q', '{created_at}'), '{updated_at}'),
            (18, 'R', '{created_at}'), '{updated_at}'),
            (19, 'S', '{created_at}'), '{updated_at}'),
            (20, 'T', '{created_at}'), '{updated_at}'),
            (21, 'U', '{created_at}'), '{updated_at}'),
            (22, 'V', '{created_at}'), '{updated_at}'),
            (23, 'W', '{created_at}'), '{updated_at}'),
            (24, 'X', '{created_at}'), '{updated_at}'),
            (25, 'Y', '{created_at}'), '{updated_at}'),
            (26, 'Z', '{created_at}'), '{updated_at}');";

        $sql .= "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_song_artists` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(128) DEFAULT NULL,
          `notes` text,
          `abjad_id` int(11) DEFAULT '0',
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sql .= "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_song_chord_refferences` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `song_id` int(11) DEFAULT '0',
          `url` text,
          `section` varchar(64) DEFAULT NULL,
          `result` text,
          `status` varchar(16) DEFAULT 'pending' COMMENT 'pending, executed, canceled, approved',
          `executed_at` datetime DEFAULT NULL,
          `approved_at` datetime DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sql .= "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_song_genres` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(16) DEFAULT NULL,
          `created_at` datetime NOT NULL,
          `updated_at` datetime NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;";

        $sql .= "INSERT INTO `tbl_ext_song_genres` (`id`, `title`, `created_at`, `updated_at`) VALUES
            (1, 'Pop', '{created_at}'), '{updated_at}'),
            (2, 'Alternatif', '{created_at}'), '{updated_at}'),
            (3, 'Blues', '{created_at}'), '{updated_at}'),
            (4, 'Country', '{created_at}'), '{updated_at}'),
            (5, 'Jazz', '{created_at}'), '{updated_at}'),
            (6, 'Reggae', '{created_at}'), '{updated_at}'),
            (7, 'Rock', '{created_at}'), '{updated_at}'),
            (8, 'Dangdut', '{created_at}'), '{updated_at}'),
            (9, 'Koplo', '{created_at}'), '{updated_at}');";

        $sql .= "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_song_lyric_refferences` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `song_id` int(11) DEFAULT '0',
          `url` text,
          `section` varchar(64) DEFAULT NULL,
          `result` text,
          `status` varchar(16) DEFAULT 'pending' COMMENT 'pending, executed, canceled, approved',
          `executed_at` datetime DEFAULT NULL,
          `approved_at` datetime DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

        $sql = str_replace(['{tablePrefix}', '{created_at}', '{updated_at}'], [$this->tablePrefix, date("Y-m-d H:i:s"), date("Y-m-d H:i:s")], $sql);
        
        $model = new \Model\OptionsModel();
        $install = $model->installExt($sql);

        return $install;
    }

    public function uninstall()
    {
        return true;
    }

    /**
     * Blog extension available menu
     * @return array
     */
    public function getMenu()
    {
        return [
            [ 'label' => 'Daftar Lagu', 'url' => 'song/summary/dashboard', 'icon' => 'fa fa-search' ],
            [ 'label' => 'Tambah Lagu', 'url' => 'song/lyrics/create', 'icon' => 'fa fa-plus' ],
        ];
    }
}
