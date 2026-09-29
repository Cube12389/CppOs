-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-09-29 10:57:59
-- 服务器版本： 5.7.26
-- PHP 版本： 7.3.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `user`
--

-- --------------------------------------------------------

--
-- 表的结构 `cook`
--

CREATE TABLE `cook` (
  `uid` int(100) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `A` longtext COLLATE utf8_unicode_ci NOT NULL,
  `B` longtext COLLATE utf8_unicode_ci NOT NULL,
  `C` longtext COLLATE utf8_unicode_ci NOT NULL,
  `D` longtext COLLATE utf8_unicode_ci NOT NULL,
  `E` longtext COLLATE utf8_unicode_ci NOT NULL,
  `F` longtext COLLATE utf8_unicode_ci NOT NULL,
  `G` longtext COLLATE utf8_unicode_ci NOT NULL,
  `H` longtext COLLATE utf8_unicode_ci NOT NULL,
  `I` longtext COLLATE utf8_unicode_ci NOT NULL,
  `J` longtext COLLATE utf8_unicode_ci NOT NULL,
  `K` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- 转存表中的数据 `cook`
--

INSERT INTO `cook` (`uid`, `name`, `A`, `B`, `C`, `D`, `E`, `F`, `G`, `H`, `I`, `J`, `K`) VALUES
(2, '贪心与证明', '// 贡献者：Cube\r\n\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nstruct Node { long long a, b; };\r\nconst int N = 1e6 + 10;\r\nvector<Node> a, b;\r\n\r\nbool cmp1(Node x, Node y) {\r\n	if (x.a != y.a) return x.a < y.a;\r\n	return x.b > y.b;\r\n}\r\n\r\nbool cmp2(Node x, Node y) {\r\n	if (x.b != y.b) return x.b > y.b;\r\n	return x.a > y.a;\r\n}\r\n\r\nint main() {\r\n	int n;\r\n	cin >> n;\r\n	for (int i = 1; i <= n; i++) {\r\n		long long x, y;\r\n		cin >> x >> y;\r\n		if (x <= y) a.push_back({x, y});\r\n		else b.push_back({x, y});\r\n	} long long ans = 0, tmp = 0;\r\n	sort(a.begin(), a.end(), cmp1);\r\n	sort(b.begin(), b.end(), cmp2);\r\n	for (Node i : a) {\r\n		if (tmp < i.a) \r\n			ans += i.a - tmp, tmp = i.a;\r\n		tmp += i.b - i.a;\r\n	} for (Node i : b) {\r\n		if (tmp < i.a) \r\n			ans += i.a - tmp, tmp = i.a;\r\n		tmp += i.b - i.a;\r\n	} cout << ans << endl;\r\n	return 0;\r\n}\r\n\r\n', '// 贡献者：Cube\r\n\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nstruct Node { int w, s, v; };\r\nconst int MAXN = 2e4;\r\nconst int N = 1e3 + 10;\r\nconst int M = 2e4 + 10;\r\nlong long dp[M];\r\nNode c[N];\r\n\r\nbool cmp(Node x, Node y) {\r\n	if (x.w + x.s != y.w + y.s) return x.w + x.s < y.w + y.s;\r\n	return x.v > y.v;\r\n}\r\n\r\nint main() {\r\n	memset(dp, -1, sizeof(dp));\r\n	int n;\r\n	cin >> n;\r\n	for (int i = 1; i <= n; i++)\r\n		cin >> c[i].w >> c[i].s >> c[i].v;\r\n	sort(c + 1, c + n + 1, cmp);\r\n	dp[0] = 0;\r\n	for (int i = 1; i <= n; i++) \r\n		for (int j = MAXN; j >= 0; j--) \r\n			if (j >= c[i].w and j - c[i].w <= c[i].s)\r\n				dp[j] = max(dp[j], dp[j - c[i].w] + c[i].v);\r\n	long long ans = 0;\r\n	for (int i = 0; i <= MAXN; i++)\r\n		ans = max(ans, dp[i]);\r\n	cout << ans << endl;\r\n	return 0;\r\n}', '// 贡献者：Cube\r\n\r\n#include <bits/stdc++.h>\r\n\r\nusing namespace std;\r\n\r\nconst int N = 2e5 + 10;\r\nint fa[N], c[N];\r\nint s[N][2];\r\nint f[N];\r\n\r\nint find(int x) {\r\n	if (f[x] == x) return x;\r\n	return f[x] = find(f[x]);\r\n}\r\n\r\nstruct Node {\r\n	int id, zero, one;\r\n	bool operator<(const Node& x) const {\r\n		if (zero == 0) return true;\r\n		if (x.zero == 0) return false;\r\n		return (long long)one * x.zero > (long long)x.one * zero;\r\n	}\r\n};\r\n\r\nint main() {\r\n	int n;\r\n	cin >> n;\r\n	for (int i = 2; i <= n; i++)\r\n		cin >> fa[i];\r\n	for (int i = 1; i <= n; i++) {\r\n		cin >> c[i];\r\n		s[i][c[i]]++;\r\n		f[i] = i;\r\n	}\r\n	priority_queue<Node> q;\r\n	for (int i = 2; i <= n; i++)\r\n		q.push({i, s[i][0], s[i][1]});\r\n	long long ans = 0;\r\n	while (!q.empty()) {\r\n		Node u = q.top();\r\n		q.pop();\r\n		if (f[u.id] != u.id) continue;\r\n		if (s[u.id][0] != u.zero || s[u.id][1] != u.one) continue;\r\n		int p = fa[u.id];\r\n		int root = find(p);\r\n		ans += (long long)s[u.id][0] * s[root][1];\r\n		s[root][0] += s[u.id][0];\r\n		s[root][1] += s[u.id][1];\r\n		f[u.id] = root;\r\n		if (root != 1)\r\n			q.push({root, s[root][0], s[root][1]});\r\n	}\r\n	cout << ans << endl;\r\n	return 0;\r\n}', '#include<bits/stdc++.h>\r\nusing namespace std;\r\nconstexpr int N=200010, INF=1e9;\r\nstruct E{int u,v,r,p,id; bool operator<(const E&o)const{return r>o.r;}}e[N];\r\nint n,m,deg[N],f[N];\r\nbool vis[N];\r\nvector<int> rg[N];\r\n\r\nint main()\r\n{\r\n    ios::sync_with_stdio(false);\r\n    cin.tie(nullptr),cout.tie(nullptr);\r\n    cin>>n>>m;\r\n    for(int i=1;i<=m;i++)\r\n        cin>>e[i].u>>e[i].v>>e[i].r>>e[i].p,e[i].id=i,deg[e[i].u]++,rg[e[i].v].push_back(i);\r\n    sort(e+1,e+m+1);\r\n    fill(f+1,f+n+1,INF);\r\n    int pos[N];\r\n    for(int i=1;i<=m;i++) pos[e[i].id]=i;\r\n    queue<int> qq;\r\n    for(int i=1;i<=n;i++) if(!deg[i]) qq.push(i);\r\n    for(int i=1;i<=m;i++)\r\n    {\r\n        while(!qq.empty())\r\n        {\r\n            int u=qq.front(); qq.pop();\r\n            for(int id:rg[u])\r\n            {\r\n                if(vis[id]) continue;\r\n                vis[id]=1,deg[e[pos[id]].u]--;\r\n                if(f[u]!=INF) f[e[pos[id]].u]=min(f[e[pos[id]].u],max(e[pos[id]].r,f[u]-e[pos[id]].p));\r\n                if(!deg[e[pos[id]].u]) qq.push(e[pos[id]].u);\r\n            }\r\n        }\r\n        if(!vis[e[i].id])\r\n        {\r\n            vis[e[i].id]=1,deg[e[i].u]--;\r\n            f[e[i].u]=min(f[e[i].u],e[i].r);\r\n            if(!deg[e[i].u]) qq.push(e[i].u);\r\n        }\r\n    }\r\n    for(int i=1;i<=n;i++)\r\n        cout<<(f[i]==INF?-1:f[i])<<\" \\n\"[i==n];\r\n    return 0;\r\n}', 'none', 'none', 'none', '#include <bits/stdc++.h>\r\nusing namespace std;\r\n\r\nconst int M = 35;\r\nconst int N = 1005;\r\nint x[M], y[M];\r\nint a[N], b[N];\r\n\r\nint main() {\r\n    int n, m1, m2;\r\n    cin >> n >> m1 >> m2;\r\n    for (int i = 1; i <= m1; i++) cin >> x[i];\r\n    for (int i = 1; i <= m2; i++) cin >> y[i];\r\n    priority_queue<pair<int,int>, vector<pair<int,int>>, greater<pair<int,int>>> pq;\r\n    for (int i = 1; i <= m1; i++) {\r\n        pq.push({x[i], i});        \r\n    }\r\n    for (int i = 1; i <= n; i++) {\r\n        auto [t, id] = pq.top(); pq.pop();\r\n        a[i] = t;                   \r\n        pq.push({t + x[id], id});  \r\n    }\r\n    sort(a + 1, a + n + 1);        \r\n    while (!pq.empty()) pq.pop();\r\n    for (int i = 1; i <= m2; i++) {\r\n        pq.push({y[i], i});\r\n    }\r\n    for (int i = 1; i <= n; i++) {\r\n        auto [t, id] = pq.top(); pq.pop();\r\n        b[i] = t;\r\n        pq.push({t + y[id], id});\r\n    }\r\n    sort(b + 1, b + n + 1, greater<int>()); \r\n    int ans1 = a[n];               \r\n    int ans2 = 0;\r\n    for (int i = 1; i <= n; i++) {\r\n        ans2 = max(ans2, a[i] + b[i]);\r\n    }\r\n    cout << ans1 << \" \" << ans2 << endl;\r\n    return 0;\r\n}', 'none', 'none', 'none');

-- --------------------------------------------------------

--
-- 表的结构 `cookie`
--

CREATE TABLE `cookie` (
  `cookie` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `uid` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- 转存表中的数据 `cookie`
--

INSERT INTO `cookie` (`cookie`, `uid`) VALUES
('38260160ad36b47ed907be51bca2fb52b2658fce0053bab5528e507a77ef0a89', 1),
('27f6a3dd91e4085ebd9e2bbf01fdd0c9af2b337c976a72876e619da8df1de51b', 3),
('b11a1ea02ac39e496316141781c39d00a35633614c7bd18382e7adffee3e7afd', 3),
('2261ce2cf1b31295ff176fc593a8b384f5803febb9b4fb4b6a347f2ed78da01f', 1),
('dc7548d59678771d5ca3ccc5c2011f51530d8993df35e0631c2cada6a2924c1a', 1),
('cbc006f32927a4e1e2d28b6e1f0a97dd31ac11bd78ce14c39e8da8c6aa8060c5', 1),
('ae9e98162d7c3a0a6b3724f7632ab3d477122202b26cb998f04bd068ab3a01c5', 1),
('21bc4c446081c706834ac513740512b5d5a90012868e3f0b771393d63c616987', 1),
('2bd5ec4432d2d8df27cf9cf38362d6c77efdc0263b3c1cbc59a43ad849eac2b9', 1),
('280d52fefbd45df02d272a2f507cfc9b6d8cba69ab6311a4acb346755f055c40', 1),
('5e12de1cc071def0973b2167fdaee4859e4d1042631206b7cae1816e35dd8366', 1),
('93b55f1a195d09e8ee562f9a6a77fa4ea328049bfb8026504dc8cb7e26a49545', 2),
('c2c0b5c6d645c8dace208bcd1b9dad828c527eff8cc3bc53dbbc44431f8c27d8', 1),
('21df097acfe69bdc5accb7e34cd2610187af1c9385994b4aff1f4970e5a5b645', 1),
('c0fe5e8332e0ce12283b88c4bbb8f8a403b78ac8af59a04b705958ca4657b46a', 2),
('05e65e1060ef36b788fe448dd7b35d10adcbbf9cd38d97d74f6361b380ea51d6', 1),
('a41824ba5aa429dc28b84eebe49cd4489f37afc8d2df0b588d193624d89ac655', 2),
('40b326377003977d11740f717506ee8dd6c6688a277c6c05b718e759d26b3d9e', 9),
('1ab04e75a6782f0b2384d05ee45473fccfc62676b331d55a5b91569e1117c674', 9),
('7df1e4a9a3feca9f1d0b78335b37d45acd36ff1728b084e072264b4f17d5b673', 9),
('458d3498579035bddd6d570c794ba31e8d7b1a3c7fa03cc83145f0a7a0b88e70', 9),
('6595329b2df4c0586bd76dd6110d2ce0da9705f001e35d7005f47b0f2da2e1d6', 9),
('cf753e8ceb8dff2534b716025f71230861516ca0c4236e9bcf74c1abdcbfc288', 1),
('4c79b52e688e61b784a0a7189061c00e342cc6f7f2b52f42f206dae124153fbf', 1),
('d0df49c02ca4669c0580070818999f04b4237583f9dc65e34e4d2f5a39dbf99f', 9),
('e20a25de0825312e511c54384937952050ac4bd3a6c721d4f6073e5e823ffcca', 1),
('0350a5bcb29beeac7dde13c63736b8aa8c318cb8d5155ae4beb57a708717ffe5', 1),
('0a4f14bb324556b04e960d9892ccc7b33adb4ad0e8f3e3ad2a3adb86a51dc80c', 1),
('a02478c877bbad6cad50b8e635715eec6c2292af83c6531e8256d485766dcf35', 9),
('9cf6e9d012474d774ab451e394103a9ab0d441b63e354a55af762324ca40d616', 1),
('4e8ab7f6f82dfe3c03c8e3c8464fb90fc13b22a2b60bb083e344970f76aaaf67', 2),
('792d66312325146eaea4ab9243ef27a664c910e457b4b02700ffc45ec95fa122', 1),
('d1155bd767c6a306b742c40a5b84979b923015f6ecbda2906a8c8d325d5e9e5e', 1),
('89eda3e1ed3e767a919feaf16e1d7fdbbb78ce8f25bc14b5eff27959caefd14d', 1);

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `uid` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `calling` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `alc` int(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`uid`, `name`, `calling`, `password`, `alc`) VALUES
(1, 'Cube', 'Aya', '$2y$10$GWkefZYRPWic1HXvA5.EWe7tTnMlo/SfJDBMWVTxPqYj.AiN.mdI6', 2),
(2, 'qwerhh', 'none', '$2y$10$SMlPcibbNiH7e.mkWz6FO.TZ5JYQVmbMwIxBbhKbFRMOF64PvFZ7W', 2);

--
-- 转储表的索引
--

--
-- 表的索引 `cook`
--
ALTER TABLE `cook`
  ADD PRIMARY KEY (`uid`);

--
-- 表的索引 `cookie`
--
ALTER TABLE `cookie`
  ADD PRIMARY KEY (`cookie`);

--
-- 表的索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
