%% name of module must match file name
-module(mod_offline_http_post).
-author("dev@codepond.org").

-behaviour(gen_mod).

-define(PROCNAME, ?MODULE).

-export([start/2, init/2, stop/1, create_message/3]).

-include("ejabberd.hrl").
-include("jlib.hrl").
-include("logger.hrl").

start(_Host, _Opts) ->
    ?INFO_MSG("Starting mod_offline_post", [] ),
    register(?PROCNAME,spawn(?MODULE, init, [_Host, _Opts])),
    ok.

init(_Host, _Opt) ->
	?INFO_MSG("mod_offline_http_post loading", []),
	inets:start(),
	?INFO_MSG("HTTP client started", []),
	ejabberd_hooks:add(offline_message_hook, _Host, ?MODULE, create_message, 10),
	ok.

stop (_Host) ->
	?INFO_MSG("stopping mod_offline_http_post", []),
	ejabberd_hooks:delete(offline_message_hook, _Host, ?MODULE, create_message, 10),
        ok.

create_message(_From, _To, Packet) ->
	Type = fxml:get_tag_attr_s(list_to_binary("type"), Packet),
	Body = fxml:get_path_s(Packet, [{elem, list_to_binary("body")}, cdata]),
	MessageId = fxml:get_tag_attr_s(list_to_binary("id"), Packet),
	if (Type == <<"chat">>) and (Body /= <<"">>) ->
		post_offline_message(_From, _To, Body, "SubType", MessageId),
                ok;
         	true ->
                ok
	end.

post_offline_message(From, To, Body, SubType, MessageId) ->
	?INFO_MSG("Posting From ~p To ~p Body ~p SubType ~p ID ~p~n",[From, To, Body, SubType, MessageId]),
	Sep = "',",
        Token = gen_mod:get_module_opt(To#jid.lserver, ?MODULE, auth_token,  fun(S) -> iolist_to_binary(S) end, list_to_binary("")),
        PostUrl = gen_mod:get_module_opt(To#jid.lserver, ?MODULE, post_url,  fun(S) -> iolist_to_binary(S) end, list_to_binary("")),
	Post = [
		"{'to':'", To#jid.luser, Sep,
        "'from':'", From#jid.luser, Sep,
		"'body':'", binary_to_list(Body), Sep,
		"'message_id':'", binary_to_list(MessageId), Sep,
		"'access_token':'", Token, "'}"
	],
	httpc:request(post, {binary_to_list(PostUrl), [], "application/json", list_to_binary(Post)},[],[]),
	?INFO_MSG("post request sent", []).
