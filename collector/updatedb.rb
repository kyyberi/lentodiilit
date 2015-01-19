####################################################
# updatedb.rb
####################################################
#
# Author: Jarkko (@kyyberi) Moilanen
# Email: jarkko@peerproduction.net



require 'nokogiri'
require 'open-uri'
require 'mysql'
require 'date'
require 'net/http'


$baseurl = "http://www.lentodiilit.fi/"

def getLinkList(cururl)
	links = Array.new
	puts "Start update\n"
	doc = Nokogiri::HTML(open(cururl))
	# get list of all deals 
	doc.xpath('//article/header/h2/a').each do |deal|
		linkurl = deal['href']
		links.push(linkurl)
	end
	return links
end

def remote_file_exists?(url)
  url = URI.parse(url)
  Net::HTTP.start(url.host, url.port) do |http|
    return http.head(url.request_uri).code == "200"
  end
end


def checkLinkList(list)
	link_list = list
        link_list.each do |item|
        	# puts item
		if(dealExists(item))
		else
			puts "new deal! Adding new item to database"
			addDeal(item)
		end
        end
end

def dealExists(url)
        begin
                #my = Mysql.new(hostname, username, password, databasename)  
                con = Mysql.new 'localhost', 'lentodiilit', '', 'lentodiilit'
                querystr = "select url from deal where url='#{url}'"
		 puts querystr
                rs = con.query(querystr)
		puts rs.num_rows
		if(rs.num_rows() == 0)
			return false
		else
			return true
		end


        rescue Mysql::Error => e
                puts e.errno
                puts e.error

        ensure
                con.close if con
        end

end


def addDeal(newurl)
	
	dealvalues = ""
	postid = ""
	posttitle = ""
	insertrs = ""
	price = ""
	time = ""
	cats = ""
	tags = ""

	# GET DETAILS
	dealpage = Nokogiri::HTML(open(newurl))
        # get list of all deals 
        dealpage.xpath('//article').each do |deal|
                postid = deal['id']
        end
	
        dealpage.xpath('//article/header/h1/a').each do |deal|
                posttitle = deal.text
        end

        price = dealpage.xpath('//article/div[@class="entry-content"]/ul/li[1]').text

	dealpage.xpath('//time').each do |deal|
                time = deal['datetime']
        end

	dealpage.xpath('//p[@class="heatmapthemead-cat-links"]/a').each do |item|
		puts item.text
		cats << item.text
		cats << " "
		puts cats
	end

        dealpage.xpath('//p[@class="heatmapthemead-tag-links"]/a').each do |item|
                puts item.text
                tags << item.text
                tags << " "
                puts tags
        end


	dealvalues = "'#{postid}','#{posttitle}','#{newurl}','#{price}', '#{time}', NOW(), '#{cats}', '#{tags}'"

   	begin 
		#my = Mysql.new(hostname, username, password, databasename)  
		con = Mysql.new 'localhost', 'lentodiilit', 'Zia5dekk', 'lentodiilit'
		insertstr = "INSERT INTO deal(postid, title, url, price, published, created, categories, tags) VALUES(#{dealvalues});"
		puts insertstr
		rs = con.query(insertstr)
		
	
	rescue Mysql::Error => e
	    	puts e.errno
	    	puts e.error
    
	ensure
    		con.close if con
	end
end


def addUpdateEvent()

	begin
                #my = Mysql.new(hostname, username, password, databasename)  
                con = Mysql.new 'localhost', 'lentodiilit', 'Zia5dekk', 'lentodiilit'
                insertstr = "INSERT INTO updateinfo(date) VALUES(NOW());"
                puts insertstr
                rs = con.query(insertstr)


        rescue Mysql::Error => e
                puts e.errno
                puts e.error

        ensure
                con.close if con
        end



end


def initUpdate()
	cururl = $baseurl 
	# puts cururl	
	if remote_file_exists?(cururl)
		link_list = Array.new
		link_list = getLinkList(cururl)
		checkLinkList(link_list)
	else
		puts "Init Failed"
	end
end

addUpdateEvent()
initUpdate()
