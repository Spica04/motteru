-- phpMyAdmin SQL Dump
-- version 4.4.15
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: 2015 年 10 月 06 日 12:27
-- サーバのバージョン： 5.6.27
-- PHP Version: 5.6.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `motteru`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `possession`
--

CREATE TABLE IF NOT EXISTS `possession` (
  `seq` bigint(20) unsigned NOT NULL COMMENT '登録SEQ',
  `uid` int(11) NOT NULL COMMENT '登録ユーザ',
  `img` varchar(100) NOT NULL COMMENT '画像パス',
  `lbl` varchar(50) NOT NULL COMMENT 'カテゴリ',
  `dt` date NOT NULL COMMENT 'データ日付',
  `num` int(11) DEFAULT NULL COMMENT 'データ番号',
  `note` text COMMENT '備考',
  `crt_datetime` datetime NOT NULL COMMENT '作成日時',
  `crt_userinfo` varchar(255) NOT NULL COMMENT '作成情報'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='所持';

-- --------------------------------------------------------

--
-- テーブルの構造 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(11) NOT NULL COMMENT 'ユーザID',
  `account` varchar(50) NOT NULL COMMENT 'アカウント',
  `password` char(64) NOT NULL COMMENT 'パスワード',
  `display` varchar(50) NOT NULL,
  `email` varchar(200) NOT NULL COMMENT 'メールアドレス',
  `status_id` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状況（0..仮,1..有効,2..無効）',
  `crt_datetime` datetime NOT NULL COMMENT '作成日時',
  `crt_usreinfo` varchar(200) NOT NULL COMMENT '作成情報'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ユーザ情報';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `possession`
--
ALTER TABLE `possession`
  ADD PRIMARY KEY (`seq`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `possession`
--
ALTER TABLE `possession`
  MODIFY `seq` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '登録SEQ';
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ユーザID';
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
