class Comment
  include Neo4j::ActiveNode
  include Neo4j::Timestamps

  searchkick

  property :content, type: String
  validates :content, presence: true

  property :visible, type: Boolean, default: true

  has_one :out, :post, type: :post, model_class: :Post
  has_one :out, :author, type: :author, model_class: :User
end
